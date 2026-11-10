<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ComplianceAppealStatus;
use App\Enums\ComplianceEventType;
use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\MerchantComplianceEvent;
use App\Models\User;
use App\Services\ComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceService();
    }

    public function test_record_compliance_event_deducts_merchant_score(): void
    {
        $merchant = Merchant::factory()->create([
            'compliance_score' => 100.00,
            'status'           => 'verified',
        ]);

        $event = $this->service->recordComplianceEvent(
            $merchant,
            ComplianceEventType::MISMATCH_CONFIRMED
        );

        $this->assertSame(-15.00, $event->score_delta);
        $this->assertSame(100.00, $event->score_before);
        $this->assertSame(85.00, $event->score_after);
        $this->assertSame(85.00, (float) $merchant->fresh()->compliance_score);
    }

    public function test_score_cannot_drop_below_zero_or_exceed_100(): void
    {
        $merchant = Merchant::factory()->create([
            'compliance_score' => 10.00,
            'status'           => 'under_review',
        ]);

        $event = $this->service->recordComplianceEvent(
            $merchant,
            ComplianceEventType::CHARGEBACK_RECEIVED // -20.00
        );

        $this->assertSame(0.00, $event->score_after);
        $this->assertSame(0.00, (float) $merchant->fresh()->compliance_score);

        // Positive recovery capped at 100
        $merchant->update(['compliance_score' => 99.00]);
        $event2 = $this->service->recordComplianceEvent(
            $merchant,
            ComplianceEventType::POSITIVE_FULFILMENT_RECOVERY // +2.00
        );

        $this->assertSame(100.00, $event2->score_after);
        $this->assertSame(100.00, (float) $merchant->fresh()->compliance_score);
    }

    public function test_score_drop_below_70_triggers_under_review_status(): void
    {
        $merchant = Merchant::factory()->create([
            'compliance_score' => 75.00,
            'status'           => 'verified',
        ]);

        $this->service->recordComplianceEvent(
            $merchant,
            ComplianceEventType::UNAUTHORIZED_SUBSTITUTION // -10.00 -> 65.00
        );

        $this->assertSame(MerchantStatus::UNDER_REVIEW, $merchant->fresh()->status);
        $this->assertDatabaseHas('merchant_status_audit_logs', [
            'merchant_id'     => $merchant->id,
            'previous_status' => 'verified',
            'new_status'      => 'under_review',
        ]);
    }

    public function test_score_drop_below_50_triggers_suspended_status(): void
    {
        $merchant = Merchant::factory()->create([
            'compliance_score' => 55.00,
            'status'           => 'under_review',
        ]);

        $this->service->recordComplianceEvent(
            $merchant,
            ComplianceEventType::MISMATCH_CONFIRMED // -15.00 -> 40.00
        );

        $this->assertSame(MerchantStatus::SUSPENDED, $merchant->fresh()->status);
        $this->assertDatabaseHas('merchant_status_audit_logs', [
            'merchant_id'     => $merchant->id,
            'previous_status' => 'under_review',
            'new_status'      => 'suspended',
        ]);
    }

    public function test_merchant_can_submit_appeal(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'merchant']);

        $event = MerchantComplianceEvent::factory()->create([
            'merchant_id'   => $merchant->id,
            'appeal_status' => ComplianceAppealStatus::NONE,
        ]);

        $appealed = $this->service->submitAppeal(
            $event,
            $user,
            reason: 'Buyer agreed to alternate color via verified support ticket.',
            evidenceUrl: 'https://example.com/ticket-123.pdf'
        );

        $this->assertSame(ComplianceAppealStatus::PENDING_REVIEW, $appealed->appeal_status);
        $this->assertNotNull($appealed->appealed_at);
        $this->assertSame('Buyer agreed to alternate color via verified support ticket.', $appealed->appeal_reason);
    }

    public function test_approved_appeal_reverses_score_deduction_and_restores_status(): void
    {
        $merchant = Merchant::factory()->create([
            'compliance_score' => 60.00,
            'status'           => 'under_review',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $event = MerchantComplianceEvent::factory()->create([
            'merchant_id'   => $merchant->id,
            'score_delta'   => -15.00,
            'appeal_status' => ComplianceAppealStatus::PENDING_REVIEW,
        ]);

        $adjudicated = $this->service->adjudicateAppeal(
            $event,
            $admin,
            approved: true,
            notes: 'Evidence verified: customer consented prior to shipment.'
        );

        $this->assertSame(ComplianceAppealStatus::APPROVED, $adjudicated->appeal_status);
        $this->assertSame(75.00, (float) $merchant->fresh()->compliance_score);
        $this->assertSame(MerchantStatus::VERIFIED, $merchant->fresh()->status);
    }

    public function test_rejected_appeal_sustains_penalty(): void
    {
        $merchant = Merchant::factory()->create(['compliance_score' => 60.00]);
        $admin = User::factory()->create(['role' => 'admin']);

        $event = MerchantComplianceEvent::factory()->create([
            'merchant_id'   => $merchant->id,
            'score_delta'   => -15.00,
            'appeal_status' => ComplianceAppealStatus::PENDING_REVIEW,
        ]);

        $adjudicated = $this->service->adjudicateAppeal(
            $event,
            $admin,
            approved: false,
            notes: 'Insufficient proof provided.'
        );

        $this->assertSame(ComplianceAppealStatus::REJECTED, $adjudicated->appeal_status);
        $this->assertSame(60.00, (float) $merchant->fresh()->compliance_score);
    }
}
