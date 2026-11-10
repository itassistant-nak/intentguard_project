<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Engines\SemanticIntegrityEngine\SemanticIntegrityEngine;
use App\Enums\VerificationVerdict;
use App\Models\PurchaseIntent;
use Tests\TestCase;

class SemanticIntegrityEngineTest extends TestCase
{
    private SemanticIntegrityEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SemanticIntegrityEngine();
    }

    private function makeIntent(): PurchaseIntent
    {
        return new PurchaseIntent([
            'merchant_id'                => 1,
            'currency'                   => 'USD',
            'quantity'                   => 1,
            'unit_price_cents'           => 10000,
            'shipping_conditions_json'   => ['shipping_cost_cents' => 500],
            'specification_json'         => [
                'brand'     => 'Sony',
                'model'     => 'WH-1000XM5',
                'condition' => 'new',
                'colour'    => 'Black',
            ],
            'allowed_substitutions_json' => [
                'colour_variants' => true,
            ],
        ]);
    }

    public function test_perfect_match_yields_1_confidence_and_match_verdict(): void
    {
        $intent = $this->makeIntent();
        $checkout = [
            'merchant_id'         => 1,
            'currency'            => 'USD',
            'quantity'            => 1,
            'unit_price_cents'    => 10000,
            'shipping_cost_cents' => 500,
            'specification_json'  => [
                'brand'     => 'Sony',
                'model'     => 'WH-1000XM5',
                'condition' => 'new',
                'colour'    => 'Black',
            ],
        ];

        $report = $this->engine->verify($intent, $checkout);

        $this->assertSame(1.0, $report->confidenceScore);
        $this->assertSame(VerificationVerdict::MATCH, $report->verdict);
        $this->assertFalse($report->holdPaymentCapture);
    }

    public function test_critical_failure_yields_0_confidence_and_mismatch_verdict(): void
    {
        $intent = $this->makeIntent();
        $checkout = [
            'merchant_id'         => 1,
            'currency'            => 'USD',
            'quantity'            => 1,
            'unit_price_cents'    => 15000, // Price surge
            'shipping_cost_cents' => 500,
            'specification_json'  => [
                'brand'     => 'Sony',
                'model'     => 'WH-1000XM5',
                'condition' => 'new',
                'colour'    => 'Black',
            ],
        ];

        $report = $this->engine->verify($intent, $checkout);

        $this->assertSame(0.0, $report->confidenceScore);
        $this->assertSame(VerificationVerdict::MISMATCH, $report->verdict);
        $this->assertTrue($report->holdPaymentCapture);
    }

    public function test_untolerated_warning_triggers_confirmation_verdict(): void
    {
        $intent = new PurchaseIntent([
            'merchant_id'                => 1,
            'currency'                   => 'USD',
            'quantity'                   => 1,
            'unit_price_cents'           => 10000,
            'shipping_conditions_json'   => ['shipping_cost_cents' => 500],
            'specification_json'         => [
                'brand'     => 'Sony',
                'model'     => 'WH-1000XM5',
                'condition' => 'new',
                'colour'    => 'Black',
            ],
            'allowed_substitutions_json' => [
                'colour_variants' => false, // Disallowed!
            ],
        ]);

        $checkout = [
            'merchant_id'         => 1,
            'currency'            => 'USD',
            'quantity'            => 1,
            'unit_price_cents'    => 10000,
            'shipping_cost_cents' => 500,
            'specification_json'  => [
                'brand'     => 'Sony',
                'model'     => 'WH-1000XM5',
                'condition' => 'new',
                'colour'    => 'Silver', // Different colour
            ],
        ];

        $report = $this->engine->verify($intent, $checkout);

        $this->assertSame(0.85, $report->confidenceScore);
        $this->assertSame(VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION, $report->verdict);
        $this->assertTrue($report->holdPaymentCapture);
    }
}
