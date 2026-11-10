<?php

declare(strict_types=1);

namespace App\Engines\TransactionDecisionEngine;

use App\Enums\CaptureAction;
use App\Enums\DecisionResolutionStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\ResolutionActorType;
use App\Enums\ResolutionAuditAction;
use App\Enums\VerificationVerdict;
use App\Models\DecisionResolutionAuditLog;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionDecisionEngine
{
    /**
     * Minimum merchant compliance score before automatic hold escalation.
     */
    public const COMPLIANCE_HOLD_THRESHOLD = 70.00;

    /**
     * Maps an integrity verdict and merchant score to an actionable gateway capture directive.
     */
    public function determineCaptureAction(
        VerificationVerdict $verdict,
        float $merchantComplianceScore = 100.00,
    ): CaptureAction {
        // High-risk compliance check: low-score merchants escalate matches to hold
        if ($merchantComplianceScore < self::COMPLIANCE_HOLD_THRESHOLD && $verdict === VerificationVerdict::MATCH) {
            return CaptureAction::CAPTURE_HOLD;
        }

        return match ($verdict) {
            VerificationVerdict::MATCH                         => CaptureAction::CAPTURE_IMMEDIATE,
            VerificationVerdict::MISMATCH                      => CaptureAction::CAPTURE_VOID,
            VerificationVerdict::UNCERTAIN                     => CaptureAction::CAPTURE_HOLD,
            VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION => CaptureAction::CAPTURE_HOLD,
        };
    }

    /**
     * Log initial hold placement when verification verdict requires capture hold.
     */
    public function recordInitialHold(VerificationDecision $decision, ?string $reason = null): DecisionResolutionAuditLog
    {
        return DecisionResolutionAuditLog::create([
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::HOLD_PLACED,
            'actor_type'               => ResolutionActorType::SYSTEM,
            'actor_id'                 => null,
            'reason'                   => $reason ?? "Payment capture hold placed: verdict [{$decision->verdict->value}].",
            'metadata_json'            => [
                'verdict'          => $decision->verdict->value,
                'confidence_score' => $decision->confidence_score,
            ],
            'created_at'               => Carbon::now(),
        ]);
    }

    /**
     * Customer explicitly confirms the deviation or acceptable substitution.
     * Releases payment capture hold and marks decision as confirmed.
     *
     * @throws \DomainException if the decision is not in a pending hold state
     */
    public function resolveCustomerConfirmation(
        VerificationDecision $decision,
        User $customer,
        ?string $notes = null,
    ): VerificationDecision {
        if (!$decision->isPendingHold()) {
            throw new \DomainException(
                "Cannot confirm verification #{$decision->id}: not in pending hold status (current: [{$decision->resolution_status->value}])."
            );
        }

        return DB::transaction(function () use ($decision, $customer, $notes) {
            $now = Carbon::now();

            $decision->update([
                'resolution_status'    => DecisionResolutionStatus::CUSTOMER_CONFIRMED,
                'hold_payment_capture' => false,
                'resolved_at'          => $now,
                'resolved_by'          => $customer->id,
            ]);

            // Update associated intent status to confirmed
            $decision->purchaseIntent->update([
                'status' => PurchaseIntentStatus::CONFIRMED,
            ]);

            // Audit: Customer Confirmed
            DecisionResolutionAuditLog::create([
                'verification_decision_id' => $decision->id,
                'action'                   => ResolutionAuditAction::CUSTOMER_CONFIRMED,
                'actor_type'               => ResolutionActorType::CUSTOMER,
                'actor_id'                 => $customer->id,
                'reason'                   => $notes ?? 'Customer explicitly confirmed acceptable variance or substitution.',
                'metadata_json'            => ['resolved_by_name' => $customer->name],
                'created_at'               => $now,
            ]);

            // Audit: Capture Released
            DecisionResolutionAuditLog::create([
                'verification_decision_id' => $decision->id,
                'action'                   => ResolutionAuditAction::CAPTURE_RELEASED,
                'actor_type'               => ResolutionActorType::SYSTEM,
                'actor_id'                 => null,
                'reason'                   => 'Payment capture hold successfully released following customer confirmation.',
                'created_at'               => $now,
            ]);

            return $decision->fresh(['resolutionAuditLogs']);
        });
    }

    /**
     * Customer rejects the deviation.
     * Voids the authorization and marks decision as customer rejected.
     *
     * @throws \DomainException if the decision is not in a pending hold state
     */
    public function resolveCustomerRejection(
        VerificationDecision $decision,
        User $customer,
        string $reason,
    ): VerificationDecision {
        if (!$decision->isPendingHold()) {
            throw new \DomainException(
                "Cannot reject verification #{$decision->id}: not in pending hold status (current: [{$decision->resolution_status->value}])."
            );
        }

        return DB::transaction(function () use ($decision, $customer, $reason) {
            $now = Carbon::now();

            $decision->update([
                'resolution_status'    => DecisionResolutionStatus::CUSTOMER_REJECTED,
                'hold_payment_capture' => true,
                'resolved_at'          => $now,
                'resolved_by'          => $customer->id,
            ]);

            // Update associated intent status to mismatched
            $decision->purchaseIntent->update([
                'status' => PurchaseIntentStatus::MISMATCHED,
            ]);

            // Audit: Customer Rejected
            DecisionResolutionAuditLog::create([
                'verification_decision_id' => $decision->id,
                'action'                   => ResolutionAuditAction::CUSTOMER_REJECTED,
                'actor_type'               => ResolutionActorType::CUSTOMER,
                'actor_id'                 => $customer->id,
                'reason'                   => $reason,
                'metadata_json'            => ['resolved_by_name' => $customer->name],
                'created_at'               => $now,
            ]);

            // Audit: Capture Voided
            DecisionResolutionAuditLog::create([
                'verification_decision_id' => $decision->id,
                'action'                   => ResolutionAuditAction::CAPTURE_VOIDED,
                'actor_type'               => ResolutionActorType::SYSTEM,
                'actor_id'                 => null,
                'reason'                   => 'Payment authorization capture voided due to customer rejection of checkout deviation.',
                'created_at'               => $now,
            ]);

            return $decision->fresh(['resolutionAuditLogs']);
        });
    }
}
