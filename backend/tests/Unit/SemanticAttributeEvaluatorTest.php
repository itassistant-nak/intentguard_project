<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Engines\SemanticIntegrityEngine\SemanticAttributeEvaluator;
use App\Enums\DiscrepancyCategory;
use App\Enums\DiscrepancySeverity;
use App\Models\PurchaseIntent;
use Tests\TestCase;

class SemanticAttributeEvaluatorTest extends TestCase
{
    private SemanticAttributeEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new SemanticAttributeEvaluator();
    }

    private function makeIntent(array $spec = [], array $substitutions = []): PurchaseIntent
    {
        return new PurchaseIntent([
            'specification_json'         => array_merge([
                'brand'     => 'Apple',
                'model'     => 'iPhone 15 Pro',
                'condition' => 'new',
                'colour'    => 'Natural Titanium',
                'storage'   => '256GB',
            ], $spec),
            'allowed_substitutions_json' => $substitutions,
        ]);
    }

    public function test_detects_unauthorized_refurbished_substitution(): void
    {
        $intent = $this->makeIntent(['condition' => 'new'], ['refurbished_ok' => false]);
        $checkout = [
            'specification_json' => [
                'condition' => 'refurbished',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::CONDITION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
        $this->assertFalse($discrepancies[0]->isTolerated);
    }

    public function test_tolerates_refurbished_substitution_when_pre_approved(): void
    {
        $intent = $this->makeIntent(['condition' => 'new'], ['refurbished_ok' => true]);
        $checkout = [
            'specification_json' => [
                'condition' => 'refurbished',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::CONDITION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::INFO, $discrepancies[0]->severity);
        $this->assertTrue($discrepancies[0]->isTolerated);
    }

    public function test_detects_brand_mismatch(): void
    {
        $intent = $this->makeIntent(['brand' => 'Apple']);
        $checkout = [
            'specification_json' => [
                'brand' => 'Samsung',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SPECIFICATION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }

    public function test_colour_variation_without_permission_produces_warning(): void
    {
        $intent = $this->makeIntent(['colour' => 'Black'], ['colour_variants' => false]);
        $checkout = [
            'specification_json' => [
                'colour' => 'Blue Titanium',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SUBSTITUTION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::WARNING, $discrepancies[0]->severity);
        $this->assertFalse($discrepancies[0]->isTolerated);
    }

    public function test_colour_variation_with_permission_is_tolerated(): void
    {
        $intent = $this->makeIntent(['colour' => 'Black'], ['colour_variants' => true]);
        $checkout = [
            'specification_json' => [
                'colour' => 'Blue Titanium',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SUBSTITUTION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::INFO, $discrepancies[0]->severity);
        $this->assertTrue($discrepancies[0]->isTolerated);
    }

    public function test_storage_downgrade_is_critical_violation(): void
    {
        $intent = $this->makeIntent(['storage' => '256GB']);
        $checkout = [
            'specification_json' => [
                'storage' => '128GB',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SPECIFICATION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::CRITICAL, $discrepancies[0]->severity);
    }

    public function test_storage_upgrade_is_tolerated_when_policy_permits(): void
    {
        $intent = $this->makeIntent(['storage' => '128GB'], ['storage_upgrade' => true]);
        $checkout = [
            'specification_json' => [
                'storage' => '256GB',
            ],
        ];

        $discrepancies = $this->evaluator->evaluate($intent, $checkout);

        $this->assertCount(1, $discrepancies);
        $this->assertSame(DiscrepancyCategory::SUBSTITUTION, $discrepancies[0]->category);
        $this->assertSame(DiscrepancySeverity::INFO, $discrepancies[0]->severity);
        $this->assertTrue($discrepancies[0]->isTolerated);
    }
}
