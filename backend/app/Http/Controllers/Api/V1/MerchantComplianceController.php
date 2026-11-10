<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ComplianceEventType;
use App\Http\Requests\AdjudicateAppealRequest;
use App\Http\Requests\RecordComplianceEventRequest;
use App\Http\Requests\SubmitAppealRequest;
use App\Http\Resources\MerchantComplianceEventResource;
use App\Models\Merchant;
use App\Models\MerchantComplianceEvent;
use App\Models\User;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MerchantComplianceController
{
    public function __construct(
        private readonly ComplianceService $complianceService,
    ) {}

    /**
     * GET /api/v1/merchants/{merchantId}/compliance/events
     *
     * List all compliance telemetry events and score history for a merchant.
     */
    public function events(Request $request, int|string $merchantId): JsonResponse|AnonymousResourceCollection
    {
        $merchant = Merchant::byIdOrIntentGuardId($merchantId)->first();

        if ($merchant === null) {
            return response()->json([
                'code'    => 'MERCHANT_NOT_FOUND',
                'message' => 'No merchant found for the given identifier.',
            ], 404);
        }

        $events = $merchant->complianceEvents()->with('adjudicator')->paginate(20);

        return MerchantComplianceEventResource::collection($events);
    }

    /**
     * POST /api/v1/merchants/{merchantId}/compliance/events
     *
     * Administrative endpoint to manually record compliance events or penalties.
     */
    public function recordEvent(RecordComplianceEventRequest $request, int|string $merchantId): JsonResponse
    {
        $merchant = Merchant::byIdOrIntentGuardId($merchantId)->first();

        if ($merchant === null) {
            return response()->json([
                'code'    => 'MERCHANT_NOT_FOUND',
                'message' => 'No merchant found for the given identifier.',
            ], 404);
        }

        $validated = $request->validated();
        $eventType = ComplianceEventType::from($validated['event_type']);

        $event = $this->complianceService->recordComplianceEvent($merchant, $eventType, $validated);

        return response()->json(new MerchantComplianceEventResource($event), 201);
    }

    /**
     * POST /api/v1/compliance/events/{id}/appeal
     *
     * Merchant submits an appeal against a compliance score deduction.
     */
    public function submitAppeal(SubmitAppealRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $event = MerchantComplianceEvent::find($id);

        if ($event === null) {
            return response()->json([
                'code'    => 'COMPLIANCE_EVENT_NOT_FOUND',
                'message' => 'No compliance event found for the given ID.',
            ], 404);
        }

        try {
            $updated = $this->complianceService->submitAppeal(
                event:       $event,
                user:        $user,
                reason:      $request->validated('reason'),
                evidenceUrl: $request->validated('appeal_evidence_url'),
            );
        } catch (\DomainException $e) {
            return response()->json([
                'code'    => 'INVALID_APPEAL_STATE',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new MerchantComplianceEventResource($updated));
    }

    /**
     * POST /api/v1/compliance/events/{id}/adjudicate
     *
     * Administrator reviews and adjudicates a merchant compliance appeal.
     */
    public function adjudicateAppeal(AdjudicateAppealRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $event = MerchantComplianceEvent::with('merchant')->find($id);

        if ($event === null) {
            return response()->json([
                'code'    => 'COMPLIANCE_EVENT_NOT_FOUND',
                'message' => 'No compliance event found for the given ID.',
            ], 404);
        }

        try {
            $updated = $this->complianceService->adjudicateAppeal(
                event:    $event,
                admin:    $user,
                approved: (bool) $request->validated('approved'),
                notes:    (string) $request->validated('notes'),
            );
        } catch (\DomainException $e) {
            return response()->json([
                'code'    => 'INVALID_APPEAL_STATE',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new MerchantComplianceEventResource($updated));
    }
}
