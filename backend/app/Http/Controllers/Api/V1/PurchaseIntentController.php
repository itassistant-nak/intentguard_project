<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\RecordPurchaseIntentRequest;
use App\Http\Resources\PurchaseIntentResource;
use App\Models\Merchant;
use App\Services\PurchaseIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseIntentController
{
    public function __construct(
        private readonly PurchaseIntentService $intentService,
    ) {}

    /**
     * POST /api/v1/intents
     *
     * Record a new purchase intent. The customer must be authenticated.
     * Returns 201 with the created intent resource including the cryptographic hash.
     */
    public function store(RecordPurchaseIntentRequest $request): JsonResponse
    {
        $merchant = Merchant::findOrFail($request->validated('merchant_id'));

        $intent = $this->intentService->record(
            customer: $request->user(),
            merchant: $merchant,
            data:     $request->validated(),
        );

        $intent->load('merchant');

        return response()->json(
            new PurchaseIntentResource($intent),
            201,
        );
    }

    /**
     * GET /api/v1/intents/{identifier}
     *
     * Retrieve a purchase intent by its internal ID or intent_hash.
     * Returns 404 if not found.
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $intent = $this->intentService->find($identifier);

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given identifier.',
            ], 404);
        }

        return response()->json(new PurchaseIntentResource($intent));
    }

    /**
     * GET /api/v1/intents
     *
     * List the authenticated customer's purchase intents (paginated).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $intents = $request->user()
            ->purchaseIntents()
            ->with('merchant')
            ->latest()
            ->paginate(15);

        return PurchaseIntentResource::collection($intents);
    }

    /**
     * POST /api/v1/intents/{id}/cancel
     *
     * Cancel an active purchase intent. Only the owning customer may cancel.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $intent = $request->user()
            ->purchaseIntents()
            ->findOrFail($id);

        try {
            $cancelled = $this->intentService->cancel($intent);
        } catch (\RuntimeException $e) {
            return response()->json([
                'code'    => 'INTENT_TERMINAL',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new PurchaseIntentResource($cancelled));
    }

    /**
     * GET /api/v1/intents/{id}/verify-integrity
     *
     * Re-runs the cryptographic hash verification against the stored intent.
     * Returns whether the stored payload matches the computed hash (tamper check).
     */
    public function verifyIntegrity(Request $request, int $id): JsonResponse
    {
        $intent = $this->intentService->find($id);

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found.',
            ], 404);
        }

        $isIntact = $this->intentService->verifyIntegrity($intent);

        return response()->json([
            'intent_id'    => $intent->id,
            'intent_hash'  => $intent->intent_hash,
            'integrity_ok' => $isIntact,
            'message'      => $isIntact
                ? 'Intent payload integrity verified — no tampering detected.'
                : 'INTEGRITY VIOLATION — stored payload does not match recorded hash.',
        ]);
    }
}
