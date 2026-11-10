<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentAuthorizationStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\VerificationVerdict;
use App\Models\CustomerDispute;
use App\Models\Merchant;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\VerificationDecision;
use App\Services\Reporting\AdminReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminReportingService();
    }

    public function test_get_platform_metrics_calculates_totals_and_fraud_prevention(): void
    {
        $merchant = Merchant::factory()->create(['compliance_score' => 90.00]);
        $intent1 = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'status'           => PurchaseIntentStatus::VERIFIED,
            'unit_price_cents' => 10000,
            'quantity'         => 1,
        ]);

        $intent2 = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'status'           => PurchaseIntentStatus::MISMATCHED,
            'unit_price_cents' => 10000,
            'quantity'         => 1,
        ]);

        // Intercepted mismatch with $30 price surge (13000 vs 10000)
        VerificationDecision::factory()->create([
            'purchase_intent_id'     => $intent2->id,
            'verdict'                => VerificationVerdict::MISMATCH,
            'discrepancy_flags_json' => [
                [
                    'category'       => 'price',
                    'intended_value' => 10000,
                    'actual_value'   => 13000,
                ],
            ],
        ]);

        // Payment authorizations
        PaymentAuthorization::factory()->captured()->create([
            'purchase_intent_id' => $intent1->id,
            'amount_cents'       => 10000,
        ]);

        PaymentAuthorization::factory()->held()->create([
            'purchase_intent_id' => $intent2->id,
            'amount_cents'       => 13000,
        ]);

        // Dispute
        CustomerDispute::factory()->create([
            'merchant_id'        => $merchant->id,
            'purchase_intent_id' => $intent1->id,
        ]);

        $metrics = $this->service->getPlatformMetrics();

        $this->assertSame(1, $metrics['fraud_prevention']['interceptions_count']);
        $this->assertSame(3000, $metrics['fraud_prevention']['prevented_surge_amount_cents']);
        $this->assertSame('30.00', $metrics['fraud_prevention']['prevented_surge_amount']);

        $this->assertSame(2, $metrics['intent_analytics']['total_intents']);
        $this->assertSame(1, $metrics['intent_analytics']['intents_by_status']['verified']);
        $this->assertSame(1, $metrics['intent_analytics']['intents_by_status']['mismatched']);

        $this->assertSame(2, $metrics['payment_analytics']['total_authorizations']);
        $this->assertSame(10000, $metrics['payment_analytics']['total_captured_amount_cents']);
        $this->assertSame('100.00', $metrics['payment_analytics']['total_captured_amount']);
        $this->assertSame(1, $metrics['payment_analytics']['held_authorizations_count']);

        $this->assertSame(1, $metrics['merchant_health']['total_merchants']);
        $this->assertSame(90.00, $metrics['merchant_health']['average_compliance_score']);

        $this->assertSame(1, $metrics['dispute_analytics']['total_disputes']);
    }
}
