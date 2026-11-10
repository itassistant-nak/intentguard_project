<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Engines\SemanticIntegrityEngine\DeterministicEvaluator;
use App\Enums\DiscrepancyCategory;
use App\Enums\DiscrepancySeverity;
use App\Models\PurchaseIntent;
use Tests\TestCase;

class DeterministicEvaluatorTest extends TestCase
{
    private DeterministicEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new DeterministicEvaluator();
    }

    private function makeIntent(array $attributes = []): PurchaseIntent
    {
        return new PurchaseIntent(array_merge([
            'merchant_id'               => 42,
            'currency'                  => 'USD',
            'quantity'                  => 2,
            'unit_price_cents'          => 5000,
            'shipping_conditions_json'  => ['shipping_cost_cents' => 500],
            'product_sku'               => 'SKU-ALPHA-1',
        ], $attributes));
    }

    public function test_clean_matching_payload_has_no_discrepancies(): void
    {
        $intent = $this->makeIntent();
        $checkout = [
            'merchant_id'         => 42,
            'currency'            => 'USD',
            'quantity'            => 2,
            'unit_price_cents'    => 5000,
            'shipping_cost_cents' => 500,
            'product_sku'         => 'SKU-ALPHA-1',
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertEmpty($discrepancies);
    }

    public function test_detects_merchant_mismatch(): void
    {
        $intent = $this->makeIntent();
        $checkout = [
            'merchant_id'         => 99,
            'currency'            => 'USD',
            'quantity'            => 2,
            'unit_price_cents'    => 5000,
            'shipping_cost_cents' => 500,
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::MERCHANT, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }

    public function test_detects_currency_mismatch(): void
    {
        $intent = $this->makeIntent();
        $checkout = [
            'merchant_id'         => 42,
            'currency'            => 'EUR',
            'quantity'            => 2,
            'unit_price_cents'    => 5000,
            'shipping_cost_cents' => 500,
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::CURRENCY, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }

    public function test_detects_unauthorized_price_surge(): void
    {
        $intent = $this->makeIntent(['unit_price_cents' => 5000]);
        $checkout = [
            'merchant_id'         => 42,
            'currency'            => 'USD',
            'quantity'            => 2,
            'unit_price_cents'    => 6500, // $15 price surge
            'shipping_cost_cents' => 500,
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::PRICE, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }

    public function test_tolerates_favorable_price_discount(): void
    {
        $intent = $this->makeIntent(['unit_price_cents' => 5000]);
        $checkout = [
            'merchant_id'         => 42,
            'currency'            => 'USD',
            'quantity'            => 2,
            'unit_price_cents'    => 4500, // $5 discount
            'shipping_cost_cents' => 500,
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::PRICE, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::INFO, $discrepancies[0]->severity);
        $this->assertTrue($discrepancies[0]->isTolerated);
    }

    public function test_detects_hidden_shipping_fee_surge(): void
    {
        $intent = $this->makeIntent(['shipping_conditions_json' => ['shipping_cost_cents' => 500]]);
        $checkout = [
            'merchant_id'         => 42,
            'currency'            => 'USD',
            'quantity'            => 2,
            'unit_price_cents'    => 5000,
            'shipping_cost_cents' => 1200, // $7 shipping surge
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SHIPPING, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }
}
