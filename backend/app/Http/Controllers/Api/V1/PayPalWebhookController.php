<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\WebhookEventResource;
use App\Models\WebhookEvent;
use App\Services\PayPal\PayPalWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayPalWebhookController
{
    public function __construct(
        private readonly PayPalWebhookService $webhookService,
    ) {}

    /**
     * POST /api/v1/webhooks/paypal
     *
     * Ingests, cryptographically verifies, and idempotently handles PayPal webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $webhookEvent = $this->webhookService->handleWebhook($request);

            if (!$webhookEvent->signature_verified) {
                return response()->json([
                    'code'    => 'WEBHOOK_SIGNATURE_INVALID',
                    'message' => 'Cryptographic transmission signature validation failed.',
                ], 400);
            }

            return (new WebhookEventResource($webhookEvent))
                ->response()
                ->setStatusCode(200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'code'    => 'INVALID_WEBHOOK_PAYLOAD',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'code'    => 'WEBHOOK_PROCESSING_ERROR',
                'message' => 'An error occurred during webhook processing: ' . $e->getMessage(),
            ], 500);
        }
    }
}
