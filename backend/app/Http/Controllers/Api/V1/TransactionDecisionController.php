<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Engines\TransactionDecisionEngine\TransactionDecisionEngine;
use App\Enums\DecisionResolutionStatus;
use App\Enums\VerificationVerdict;
use App\Http\Requests\ConfirmResolutionRequest;
use App\Http\Requests\RejectResolutionRequest;
use App\Http\Resources\DecisionResolutionAuditLogResource;
use App\Http\Resources\VerificationDecisionResource;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionDecisionController
{
    public function __construct(
        private readonly TransactionDecisionEngine $decisionEngine,
    ) {}

    /**
     * POST /api/v1/verifications/{id}/confirm
     *
     * Customer 1-click confirmation for transactions held due to acceptable
     * substitutions or minor variations. Releases capture hold.
     */
    public function confirm(ConfirmResolutionRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $decision = $this->resolveDecision($id, $user);

        if ($decision === null) {
            return $this->notFoundResponse();
        }

        try {
            $updated = $this->decisionEngine->resolveCustomerConfirmation(
                decision: $decision,
                customer: $user,
                notes:    $request->validated('notes'),
            );
        } catch (\DomainException $e) {
            return response()->json([
                'code'    => 'INVALID_RESOLUTION_STATE',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new VerificationDecisionResource($updated));
    }

    /**
     * POST /api/v1/verifications/{id}/reject
     *
     * Customer rejects transaction deviation. Confirms mismatch, voids capture,
     * and logs resolution audit trail.
     */
    public function reject(RejectResolutionRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $decision = $this->resolveDecision($id, $user);

        if ($decision === null) {
            return $this->notFoundResponse();
        }

        try {
            $updated = $this->decisionEngine->resolveCustomerRejection(
                decision: $decision,
                customer: $user,
                reason:   $request->validated('reason'),
            );
        } catch (\DomainException $e) {
            return response()->json([
                'code'    => 'INVALID_RESOLUTION_STATE',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new VerificationDecisionResource($updated));
    }

    /**
     * GET /api/v1/verifications/{id}/audit-log
     *
     * Retrieve resolution audit history for a verification decision.
     */
    public function auditLog(Request $request, int $id): JsonResponse|AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $decision = $this->resolveDecision($id, $user);

        if ($decision === null) {
            return $this->notFoundResponse();
        }

        $logs = $decision->resolutionAuditLogs()->with('actor')->get();

        return DecisionResolutionAuditLogResource::collection($logs);
    }

    /**
     * GET /api/v1/verifications/pending-holds
     *
     * List all verification decisions currently in a pending hold state
     * awaiting customer confirmation or review.
     */
    public function pendingHolds(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = VerificationDecision::where('hold_payment_capture', true)
            ->where('resolution_status', DecisionResolutionStatus::UNRESOLVED)
            ->whereIn('verdict', [
                VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION,
                VerificationVerdict::UNCERTAIN,
            ])
            ->with(['purchaseIntent', 'resolutionAuditLogs'])
            ->latest('decided_at');

        if (!$user->isAdmin()) {
            $query->whereHas('purchaseIntent', function ($q) use ($user) {
                $q->where('customer_id', $user->id);
            });
        }

        $decisions = $query->paginate(15);

        return VerificationDecisionResource::collection($decisions);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveDecision(int $id, User $user): ?VerificationDecision
    {
        $decision = VerificationDecision::with('purchaseIntent')->find($id);

        if ($decision === null) {
            return null;
        }

        if (!$user->isAdmin() && $decision->purchaseIntent->customer_id !== $user->id) {
            return null;
        }

        return $decision;
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'code'    => 'VERIFICATION_NOT_FOUND',
            'message' => 'No verification decision found, or access denied.',
        ], 404);
    }
}
