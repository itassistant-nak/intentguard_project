<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Enums\PaymentAuthorizationStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\VerificationVerdict;
use App\Models\CustomerDispute;
use App\Models\Merchant;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\SystemAuditLog;
use App\Models\VerificationDecision;

class AdminReportingService
{
    /**
     * Aggregates platform-wide transaction integrity, fraud prevention, and compliance metrics.
     *
     * @return array<string, mixed>
     */
    public function getPlatformMetrics(): array
    {
        // 1. Fraud & Mismatch Prevention Impact
        $mismatchDecisions = VerificationDecision::whereIn('verdict', [
            VerificationVerdict::MISMATCH,
            VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION,
        ])->get();

        $interceptionsCount = $mismatchDecisions->count();
        $preventedSurgeCents = 0;

        foreach ($mismatchDecisions as $decision) {
            $discrepancies = $decision->discrepancy_flags_json ?? [];
            foreach ($discrepancies as $flag) {
                if (($flag['category'] ?? '') === 'price') {
                    $intended = (int) ($flag['intended_value'] ?? 0);
                    $actual = (int) ($flag['actual_value'] ?? 0);
                    if ($actual > $intended) {
                        $preventedSurgeCents += ($actual - $intended);
                    }
                }
            }
        }

        // 2. Purchase Intent Metrics
        $totalIntents = PurchaseIntent::count();
        $intentsByStatus = [];
        foreach (PurchaseIntentStatus::cases() as $case) {
            $intentsByStatus[$case->value] = PurchaseIntent::where('status', $case)->count();
        }

        // 3. Payment Gateway Metrics
        $totalAuths = PaymentAuthorization::count();
        $capturedAmountCents = (int) PaymentAuthorization::where('status', PaymentAuthorizationStatus::CAPTURED)->sum('amount_cents');
        $heldCount = PaymentAuthorization::where('status', PaymentAuthorizationStatus::HELD)->count();
        $voidedCount = PaymentAuthorization::where('status', PaymentAuthorizationStatus::VOIDED)->count();

        // 4. Merchant Health Metrics
        $totalMerchants = Merchant::count();
        $avgScore = Merchant::avg('compliance_score');
        $merchantsByStatus = Merchant::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 5. Dispute Adjudication Metrics
        $totalDisputes = CustomerDispute::count();
        $openDisputes = CustomerDispute::whereIn('status', ['opened', 'under_merchant_response', 'escalated_to_admin'])->count();
        $resolvedDisputes = CustomerDispute::whereIn('status', ['resolved_refunded', 'resolved_replaced', 'resolved_rejected'])->count();

        // 6. Audit & Provenance Health
        $totalAuditRecords = SystemAuditLog::count();
        $latestAudit = SystemAuditLog::latest('id')->first();

        return [
            'fraud_prevention' => [
                'interceptions_count'           => $interceptionsCount,
                'prevented_surge_amount_cents'  => $preventedSurgeCents,
                'prevented_surge_amount'        => number_format($preventedSurgeCents / 100, 2),
            ],
            'intent_analytics' => [
                'total_intents'     => $totalIntents,
                'intents_by_status' => $intentsByStatus,
            ],
            'payment_analytics' => [
                'total_authorizations'       => $totalAuths,
                'total_captured_amount_cents' => $capturedAmountCents,
                'total_captured_amount'       => number_format($capturedAmountCents / 100, 2),
                'held_authorizations_count'  => $heldCount,
                'voided_authorizations_count'=> $voidedCount,
            ],
            'merchant_health' => [
                'total_merchants'         => $totalMerchants,
                'average_compliance_score'=> round((float) ($avgScore ?? 100.00), 2),
                'merchants_by_status'     => $merchantsByStatus,
            ],
            'dispute_analytics' => [
                'total_disputes'    => $totalDisputes,
                'open_disputes'     => $openDisputes,
                'resolved_disputes' => $resolvedDisputes,
            ],
            'audit_provenance' => [
                'total_audit_records' => $totalAuditRecords,
                'latest_tamper_hash'  => $latestAudit?->tamper_hash,
                'last_audit_at'       => $latestAudit?->created_at->toISOString(),
            ],
        ];
    }
}
