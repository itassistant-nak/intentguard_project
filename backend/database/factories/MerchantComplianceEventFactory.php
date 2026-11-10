<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComplianceAppealStatus;
use App\Enums\ComplianceEventType;
use App\Models\Merchant;
use App\Models\MerchantComplianceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantComplianceEvent>
 */
class MerchantComplianceEventFactory extends Factory
{
    protected $model = MerchantComplianceEvent::class;

    public function definition(): array
    {
        $eventType = ComplianceEventType::MISMATCH_CONFIRMED;
        $scoreDelta = $eventType->defaultScoreDelta();

        return [
            'merchant_id'              => Merchant::factory(),
            'purchase_intent_id'       => null,
            'verification_decision_id' => null,
            'event_type'               => $eventType,
            'severity'                 => $eventType->defaultSeverity(),
            'score_delta'              => $scoreDelta,
            'score_before'             => 100.00,
            'score_after'              => 85.00,
            'notes'                    => 'Discrepancy confirmed during checkout verification.',
            'evidence_reference'       => 'https://evidence.intentguard.internal/snapshots/mismatch-1.html',
            'appeal_status'            => ComplianceAppealStatus::NONE,
            'created_at'               => now(),
        ];
    }

    public function appealPending(): static
    {
        return $this->state([
            'appeal_status'       => ComplianceAppealStatus::PENDING_REVIEW,
            'appeal_reason'       => 'The buyer agreed to the updated model variant in writing.',
            'appeal_evidence_url' => 'https://evidence.example.com/buyer-chat.pdf',
            'appealed_at'         => now(),
        ]);
    }
}
