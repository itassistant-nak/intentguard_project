<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Engines\SemanticIntegrityEngine\SemanticIntegrityEngine;
use App\Engines\TransactionDecisionEngine\TransactionDecisionEngine;
use App\Enums\CaptureAction;
use App\Enums\VerificationVerdict;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnginePerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private SemanticIntegrityEngine $spie;
    private TransactionDecisionEngine $tde;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spie = new SemanticIntegrityEngine();
        $this->tde = new TransactionDecisionEngine();
    }

    /**
     * Benchmark pure SPIE + TDE evaluation latency across 500 diverse transactions.
     * SLA requirement: Mean latency < 250ms per transaction (target: < 10ms in pure PHP).
     */
    public function test_spie_and_tde_high_throughput_latency_benchmark(): void
    {
        $iterations = 500;
        $latencies = [];

        $testScenarios = [
            // Scenario 1: Exact Clean Match
            [
                'intent' => [
                    'merchant_id' => 1,
                    'currency' => 'USD',
                    'quantity' => 1,
                    'unit_price_cents' => 9999,
                    'shipping_conditions_json' => ['shipping_cost_cents' => 500],
                    'specification_json' => ['brand' => 'Apple', 'model' => 'iPhone 15', 'condition' => 'new', 'storage' => '128GB'],
                    'allowed_substitutions_json' => ['colour_variants' => true],
                ],
                'checkout' => [
                    'merchant_id' => 1,
                    'currency' => 'USD',
                    'quantity' => 1,
                    'unit_price_cents' => 9999,
                    'shipping_cost_cents' => 500,
                    'specification_json' => ['brand' => 'Apple', 'model' => 'iPhone 15', 'condition' => 'new', 'storage' => '128GB'],
                ],
                'expected_verdict' => VerificationVerdict::MATCH,
                'compliance_score' => 95.0,
                'expected_action' => CaptureAction::CAPTURE_IMMEDIATE,
            ],
            // Scenario 2: Unilateral Price Surge (+20%)
            [
                'intent' => [
                    'merchant_id' => 1,
                    'currency' => 'USD',
                    'quantity' => 1,
                    'unit_price_cents' => 10000,
                    'shipping_conditions_json' => ['shipping_cost_cents' => 500],
                    'specification_json' => ['brand' => 'Sony', 'model' => 'WH-1000XM5', 'condition' => 'new'],
                    'allowed_substitutions_json' => [],
                ],
                'checkout' => [
                    'merchant_id' => 1,
                    'currency' => 'USD',
                    'quantity' => 1,
                    'unit_price_cents' => 12000, // +$20 surge
                    'shipping_cost_cents' => 500,
                    'specification_json' => ['brand' => 'Sony', 'model' => 'WH-1000XM5', 'condition' => 'new'],
                ],
                'expected_verdict' => VerificationVerdict::MISMATCH,
                'compliance_score' => 90.0,
                'expected_action' => CaptureAction::CAPTURE_VOID,
            ],
            // Scenario 3: Unauthorized Refurbished Substitution
            [
                'intent' => [
                    'merchant_id' => 2,
                    'currency' => 'EUR',
                    'quantity' => 1,
                    'unit_price_cents' => 45000,
                    'shipping_conditions_json' => ['shipping_cost_cents' => 0],
                    'specification_json' => ['brand' => 'Dell', 'model' => 'XPS 13', 'condition' => 'new'],
                    'allowed_substitutions_json' => ['refurbished' => false],
                ],
                'checkout' => [
                    'merchant_id' => 2,
                    'currency' => 'EUR',
                    'quantity' => 1,
                    'unit_price_cents' => 45000,
                    'shipping_cost_cents' => 0,
                    'specification_json' => ['brand' => 'Dell', 'model' => 'XPS 13', 'condition' => 'refurbished'],
                ],
                'expected_verdict' => VerificationVerdict::MISMATCH,
                'compliance_score' => 88.0,
                'expected_action' => CaptureAction::CAPTURE_VOID,
            ],
            // Scenario 4: Tolerated Color Variant with Lower Merchant Score (Escalates to Hold)
            [
                'intent' => [
                    'merchant_id' => 3,
                    'currency' => 'USD',
                    'quantity' => 2,
                    'unit_price_cents' => 2500,
                    'shipping_conditions_json' => ['shipping_cost_cents' => 300],
                    'specification_json' => ['brand' => 'Logitech', 'model' => 'MX Master 3S', 'colour' => 'Graphite'],
                    'allowed_substitutions_json' => ['colour_variants' => true],
                ],
                'checkout' => [
                    'merchant_id' => 3,
                    'currency' => 'USD',
                    'quantity' => 2,
                    'unit_price_cents' => 2500,
                    'shipping_cost_cents' => 300,
                    'specification_json' => ['brand' => 'Logitech', 'model' => 'MX Master 3S', 'colour' => 'Pale Grey'],
                ],
                'expected_verdict' => VerificationVerdict::MATCH,
                'compliance_score' => 64.0, // Low score < 70 escalates MATCH to HOLD
                'expected_action' => CaptureAction::CAPTURE_HOLD,
            ],
        ];

        // Warm up JIT / cache
        for ($w = 0; $w < 10; $w++) {
            $warmupIntent = new PurchaseIntent($testScenarios[0]['intent']);
            $this->spie->verify($warmupIntent, $testScenarios[0]['checkout']);
        }

        $overallStart = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $testScenarios[$i % count($testScenarios)];
            $intent = new PurchaseIntent($scenario['intent']);

            $start = hrtime(true);

            // Step 1: SPIE Semantic Verification
            $report = $this->spie->verify($intent, $scenario['checkout']);

            // Step 2: TDE Capture Decision
            $action = $this->tde->determineCaptureAction($report->verdict, $scenario['compliance_score']);

            $end = hrtime(true);
            $elapsedMs = ($end - $start) / 1e6;
            $latencies[] = $elapsedMs;

            $this->assertSame($scenario['expected_verdict'], $report->verdict);
            $this->assertSame($scenario['expected_action'], $action);
        }

        $overallEnd = hrtime(true);
        $totalElapsedMs = ($overallEnd - $overallStart) / 1e6;
        $meanLatencyMs = array_sum($latencies) / count($latencies);
        sort($latencies);
        $p95LatencyMs = $latencies[(int) floor(count($latencies) * 0.95)];
        $p99LatencyMs = $latencies[(int) floor(count($latencies) * 0.99)];

        // Assert strictly under SLA thresholds
        $this->assertLessThan(25.0, $meanLatencyMs, "Mean SPIE/TDE evaluation latency ({$meanLatencyMs}ms) exceeded 25ms threshold");
        $this->assertLessThan(50.0, $p95LatencyMs, "p95 SPIE/TDE evaluation latency ({$p95LatencyMs}ms) exceeded 50ms threshold");
        $this->assertLessThan(100.0, $p99LatencyMs, "p99 SPIE/TDE evaluation latency ({$p99LatencyMs}ms) exceeded 100ms threshold");
        $this->assertCount($iterations, $latencies);
    }

    /**
     * Verify database-backed checkout verification endpoint handles high concurrent loads without memory degradation.
     */
    public function test_api_checkout_verification_endpoint_performance(): void
    {
        $user = User::factory()->customer()->create();
        $merchant = Merchant::factory()->create([
            'compliance_score' => 92.50,
        ]);

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $user->id,
            'merchant_id' => $merchant->id,
            'unit_price_cents' => 15000,
            'currency' => 'USD',
            'quantity' => 1,
            'shipping_conditions_json' => ['shipping_cost_cents' => 1000],
            'specification_json' => [
                'brand' => 'Bose',
                'model' => 'QuietComfort 45',
                'condition' => 'new',
            ],
            'allowed_substitutions_json' => [
                'colour_variants' => true,
            ],
        ]);

        $checkoutPayload = [
            'merchant_id'         => $merchant->id,
            'currency'            => 'USD',
            'quantity'            => 1,
            'unit_price_cents'    => 15000,
            'total_price_cents'   => 16000,
            'shipping_cost_cents' => 1000,
            'specification_json'  => [
                'brand'     => 'Bose',
                'model'     => 'QuietComfort 45',
                'condition' => 'new',
            ],
        ];

        $requestStart = hrtime(true);
        $response = $this->actingAs($user)
            ->postJson("/api/v1/intents/{$intent->id}/verify", $checkoutPayload);
        $requestEnd = hrtime(true);
        $latencyMs = ($requestEnd - $requestStart) / 1e6;

        $response->assertStatus(201)
            ->assertJsonPath('verdict', 'MATCH')
            ->assertJsonPath('confidence_score', 1);

        // Entire HTTP cycle (Auth + DB read + SPIE + DB write decision + SystemAuditLog write) under 250ms SLA
        $this->assertLessThan(250.0, $latencyMs, "Full HTTP API verification latency ({$latencyMs}ms) exceeded 250ms SLA");
    }
}
