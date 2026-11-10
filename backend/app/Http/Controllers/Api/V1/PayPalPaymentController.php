<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AuthorizePayPalOrderRequest;
use App\Http\Requests\CaptureHeldPaymentRequest;
use App\Http\Requests\CreatePayPalOrderRequest;
use App\Http\Requests\VoidHeldPaymentRequest;
use App\Http\Resources\PaymentAuthorizationResource;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\PayPal\PayPalGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayPalPaymentController
{
    public function __construct(
        private readonly PayPalGatewayService $gatewayService,
    ) {}

    /**
     * POST /api/v1/intents/{intentId}/paypal/order
     *
     * Creates an Orders v2 order on PayPal with intent="AUTHORIZE".
     */
    public function createOrder(CreatePayPalOrderRequest $request, int $intentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $intent = PurchaseIntent::where('id', $intentId)
            ->where('customer_id', $user->id)
            ->first();

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given ID, or access denied.',
            ], 404);
        }

        $validated = $request->validated();

        $paymentAuth = $this->gatewayService->createOrderForIntent(
            intent:         $intent,
            idempotencyKey: $validated['idempotency_key'],
            options:        [
                'return_url' => $validated['return_url'] ?? null,
                'cancel_url' => $validated['cancel_url'] ?? null,
            ]
        );

        return (new PaymentAuthorizationResource($paymentAuth))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/v1/intents/{intentId}/paypal/authorize
     *
     * Authorizes an approved PayPal order and intercepts capture with SPIE and TDE.
     */
    public function authorizeOrder(AuthorizePayPalOrderRequest $request, int $intentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $intent = PurchaseIntent::where('id', $intentId)
            ->where('customer_id', $user->id)
            ->with('merchant')
            ->first();

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given ID, or access denied.',
            ], 404);
        }

        $validated = $request->validated();

        $paymentAuth = $this->gatewayService->processAuthorization(
            intent:          $intent,
            orderId:         $validated['order_id'],
            checkoutPayload: $validated['checkout_payload'],
            idempotencyKey:  $validated['idempotency_key'] ?? null,
        );

        return (new PaymentAuthorizationResource($paymentAuth))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * GET /api/v1/payments/{id}
     *
     * Inspects payment authorization details, status, and linked verification decision.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $paymentAuth = PaymentAuthorization::with(['purchaseIntent', 'verificationDecision'])
            ->find($id);

        if ($paymentAuth === null) {
            return response()->json([
                'code'    => 'PAYMENT_NOT_FOUND',
                'message' => 'No payment authorization found for the given ID.',
            ], 404);
        }

        // Access control: customer who owns the intent or admin
        if ($user->role !== 'admin' && $paymentAuth->purchaseIntent->customer_id !== $user->id) {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'You do not have permission to view this payment authorization.',
            ], 403);
        }

        return (new PaymentAuthorizationResource($paymentAuth))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * POST /api/v1/payments/{id}/capture-held
     *
     * Customer/Admin releases capture hold and captures payment on PayPal.
     */
    public function captureHeld(CaptureHeldPaymentRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $paymentAuth = PaymentAuthorization::with(['purchaseIntent', 'verificationDecision'])->find($id);

        if ($paymentAuth === null) {
            return response()->json([
                'code'    => 'PAYMENT_NOT_FOUND',
                'message' => 'No payment authorization found for the given ID.',
            ], 404);
        }

        // Access control: customer owner or admin
        if ($user->role !== 'admin' && $paymentAuth->purchaseIntent->customer_id !== $user->id) {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'You do not have permission to release this payment hold.',
            ], 403);
        }

        if (!$paymentAuth->isHeld()) {
            return response()->json([
                'code'    => 'INVALID_PAYMENT_STATE',
                'message' => "Payment authorization is in status [{$paymentAuth->status->value}], not HELD.",
            ], 422);
        }

        $reason = $request->validated()['reason'] ?? 'Customer approved held transaction.';

        $resolvedAuth = $this->gatewayService->executeHeldCapture($paymentAuth, $user, $reason);

        return (new PaymentAuthorizationResource($resolvedAuth))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * POST /api/v1/payments/{id}/void-held
     *
     * Customer/Admin rejects held transaction and voids authorization on PayPal.
     */
    public function voidHeld(VoidHeldPaymentRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $paymentAuth = PaymentAuthorization::with(['purchaseIntent', 'verificationDecision'])->find($id);

        if ($paymentAuth === null) {
            return response()->json([
                'code'    => 'PAYMENT_NOT_FOUND',
                'message' => 'No payment authorization found for the given ID.',
            ], 404);
        }

        // Access control: customer owner or admin
        if ($user->role !== 'admin' && $paymentAuth->purchaseIntent->customer_id !== $user->id) {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'You do not have permission to void this payment authorization.',
            ], 403);
        }

        if (!$paymentAuth->isHeld()) {
            return response()->json([
                'code'    => 'INVALID_PAYMENT_STATE',
                'message' => "Payment authorization is in status [{$paymentAuth->status->value}], not HELD.",
            ], 422);
        }

        $reason = $request->validated()['reason'];

        $resolvedAuth = $this->gatewayService->executeHeldVoid($paymentAuth, $user, $reason);

        return (new PaymentAuthorizationResource($resolvedAuth))
            ->response()
            ->setStatusCode(200);
    }
}
