<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ComplianceAppealStatus;
use App\Enums\ComplianceEventType;
use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Enums\MerchantStatus;
use App\Models\CustomerDispute;
use App\Models\Merchant;
use App\Models\MerchantComplianceEvent;
use App\Models\MerchantStatusAuditLog;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComplianceService
{
    /**
     * Records a compliance telemetry event, updates merchant compliance score,
     * and evaluates contractual threshold state transitions.
     *
     * @param  array<string, mixed> $options
     */
    public function recordComplianceEvent(
        Merchant $merchant,
        ComplianceEventType $type,
        array $options = [],
    ): MerchantComplianceEvent {
        return DB::transaction(function () use ($merchant, $type, $options) {
            $scoreDelta = (float) ($options['score_delta'] ?? $type->defaultScoreDelta());
            $scoreBefore = (float) $merchant->compliance_score;
            $scoreAfter = (float) min(100.00, max(0.00, $scoreBefore + $scoreDelta));

            // Update merchant compliance score
            $merchant->update(['compliance_score' => $scoreAfter]);

            // Threshold enforcement:
            // Score < 50.00 -> automated suspension
            // Score < 70.00 -> placed under review
            $this->evaluateScoreThresholds($merchant, $scoreBefore, $scoreAfter);

            return MerchantComplianceEvent::create([
                'merchant_id'              => $merchant->id,
                'purchase_intent_id'       => $options['purchase_intent_id'] ?? null,
                'verification_decision_id' => $options['verification_decision_id'] ?? null,
                'event_type'               => $type,
                'severity'                 => $options['severity'] ?? $type->defaultSeverity(),
                'score_delta'              => $scoreDelta,
                'score_before'             => $scoreBefore,
                'score_after'              => $scoreAfter,
                'notes'                    => $options['notes'] ?? null,
                'evidence_reference'       => $options['evidence_reference'] ?? null,
                'appeal_status'            => ComplianceAppealStatus::NONE,
                'created_at'               => Carbon::now(),
            ]);
        });
    }

    /**
     * Submit an appeal against a compliance event penalty.
     */
    public function submitAppeal(
        MerchantComplianceEvent $event,
        User $user,
        string $reason,
        ?string $evidenceUrl = null,
    ): MerchantComplianceEvent {
        if ($event->appeal_status !== ComplianceAppealStatus::NONE) {
            throw new \DomainException("An appeal has already been filed for compliance event #{$event->id}.");
        }

        $event->update([
            'appeal_status'       => ComplianceAppealStatus::PENDING_REVIEW,
            'appeal_reason'       => $reason,
            'appeal_evidence_url' => $evidenceUrl,
            'appealed_at'         => Carbon::now(),
        ]);

        return $event->fresh();
    }

    /**
     * Adjudicate an appeal. If approved, the score deduction is reversed.
     */
    public function adjudicateAppeal(
        MerchantComplianceEvent $event,
        User $admin,
        bool $approved,
        string $notes,
    ): MerchantComplianceEvent {
        if (!$event->appeal_status->isPending()) {
            throw new \DomainException("Compliance event #{$event->id} is not awaiting appeal review.");
        }

        return DB::transaction(function () use ($event, $admin, $approved, $notes) {
            $now = Carbon::now();

            if ($approved && $event->score_delta < 0) {
                // Reverse the penalty
                $merchant = $event->merchant;
                $restorationAmount = abs($event->score_delta);
                $newScore = min(100.00, (float) $merchant->compliance_score + $restorationAmount);

                $merchant->update(['compliance_score' => $newScore]);

                // Check if restored score lifts merchant back to verified
                $isRestricted = in_array($merchant->status, [MerchantStatus::UNDER_REVIEW, MerchantStatus::SUSPENDED], true);
                if ($newScore >= 70.00 && $isRestricted) {
                    $prevStatus = $merchant->status instanceof MerchantStatus ? $merchant->status->value : (string) $merchant->status;
                    $merchant->update(['status' => MerchantStatus::VERIFIED]);
                    MerchantStatusAuditLog::create([
                        'merchant_id'     => $merchant->id,
                        'previous_status' => $prevStatus,
                        'new_status'      => MerchantStatus::VERIFIED->value,
                        'reason'          => "Status automatically restored to verified following approved appeal #{$event->id}.",
                        'actioned_by'     => $admin->id,
                    ]);
                }
            }

            $event->update([
                'appeal_status'      => $approved ? ComplianceAppealStatus::APPROVED : ComplianceAppealStatus::REJECTED,
                'adjudicated_at'     => $now,
                'adjudicated_by'     => $admin->id,
                'adjudication_notes' => $notes,
            ]);

            return $event->fresh(['merchant', 'adjudicator']);
        });
    }

    /**
     * Open a customer dispute against a purchase intent and merchant.
     */
    public function openDispute(
        PurchaseIntent $intent,
        User $customer,
        DisputeCategory $category,
        string $description,
        ?int $claimedAmountCents = null,
        ?int $evidenceSnapshotId = null,
    ): CustomerDispute {
        return DB::transaction(function () use ($intent, $customer, $category, $description, $claimedAmountCents, $evidenceSnapshotId) {
            $dispute = CustomerDispute::create([
                'purchase_intent_id'   => $intent->id,
                'customer_id'          => $customer->id,
                'merchant_id'          => $intent->merchant_id,
                'dispute_category'     => $category,
                'description'          => $description,
                'claimed_amount_cents' => $claimedAmountCents,
                'evidence_snapshot_id' => $evidenceSnapshotId,
                'status'               => DisputeStatus::OPENED,
            ]);

            // Automatically emit a DISPUTE_FILED compliance event against the merchant
            $this->recordComplianceEvent($intent->merchant, ComplianceEventType::DISPUTE_FILED, [
                'purchase_intent_id' => $intent->id,
                'notes'              => "Customer dispute #{$dispute->id} filed: {$category->label()}.",
            ]);

            return $dispute;
        });
    }

    /**
     * Record merchant response to an open customer dispute.
     */
    public function respondToDispute(
        CustomerDispute $dispute,
        string $merchantResponse,
    ): CustomerDispute {
        $dispute->update([
            'merchant_response'     => $merchantResponse,
            'merchant_responded_at' => Carbon::now(),
            'status'                => DisputeStatus::UNDER_MERCHANT_RESPONSE,
        ]);

        return $dispute->fresh();
    }

    /**
     * Formally resolve a customer dispute.
     */
    public function resolveDispute(
        CustomerDispute $dispute,
        User $resolver,
        DisputeStatus $status,
        string $notes,
    ): CustomerDispute {
        if (!$status->isTerminal()) {
            throw new \DomainException("Status [{$status->value}] is not a valid terminal resolution state.");
        }

        $dispute->update([
            'status'           => $status,
            'resolution_notes' => $notes,
            'resolved_at'      => Carbon::now(),
            'resolved_by'      => $resolver->id,
        ]);

        return $dispute->fresh(['resolver']);
    }

    /**
     * Evaluates merchant contractual thresholds and initiates audit status transitions.
     */
    private function evaluateScoreThresholds(Merchant $merchant, float $scoreBefore, float $scoreAfter): void
    {
        if ($scoreAfter < 50.00 && $merchant->status !== MerchantStatus::SUSPENDED) {
            $prev = $merchant->status instanceof MerchantStatus ? $merchant->status->value : (string) $merchant->status;
            $merchant->update(['status' => MerchantStatus::SUSPENDED]);
            MerchantStatusAuditLog::create([
                'merchant_id'     => $merchant->id,
                'previous_status' => $prev,
                'new_status'      => MerchantStatus::SUSPENDED->value,
                'reason'          => "Contractual compliance penalty: score dropped to {$scoreAfter}% (below 50% critical threshold).",
                'actioned_by'     => null,
            ]);
        } elseif ($scoreAfter < 70.00 && $merchant->status === MerchantStatus::VERIFIED) {
            $prev = $merchant->status instanceof MerchantStatus ? $merchant->status->value : (string) $merchant->status;
            $merchant->update(['status' => MerchantStatus::UNDER_REVIEW]);
            MerchantStatusAuditLog::create([
                'merchant_id'     => $merchant->id,
                'previous_status' => $prev,
                'new_status'      => MerchantStatus::UNDER_REVIEW->value,
                'reason'          => "Compliance score dropped to {$scoreAfter}% (below 70% standard threshold). Review required.",
                'actioned_by'     => null,
            ]);
        }
    }
}
