<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VerificationVerdict;
use App\Models\PurchaseIntent;
use App\Models\VerificationDecision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationDecision>
 */
class VerificationDecisionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<VerificationDecision>
     */
    protected $model = VerificationDecision::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_intent_id'     => PurchaseIntent::factory(),
            'checkout_payload_json'  => [
                'merchant_id'         => 1,
                'currency'            => 'USD',
                'unit_price_cents'    => 9999,
                'quantity'            => 1,
                'total_price_cents'   => 9999,
                'shipping_cost_cents' => 599,
                'product_name'        => 'Wireless Headphones',
            ],
            'discrepancy_flags_json' => [],
            'confidence_score'       => 1.0000,
            'verdict'                => VerificationVerdict::MATCH,
            'hold_payment_capture'   => false,
            'decided_at'             => now(),
        ];
    }

    /**
     * State: mismatch verdict.
     */
    public function mismatch(): static
    {
        return $this->state([
            'confidence_score'       => 0.0000,
            'verdict'                => VerificationVerdict::MISMATCH,
            'hold_payment_capture'   => true,
            'discrepancy_flags_json' => [
                [
                    'category'       => 'price',
                    'severity'       => 'critical',
                    'field'          => 'unit_price_cents',
                    'intended_value' => 9999,
                    'actual_value'   => 12999,
                    'message'        => 'Unauthorized price surge detected.',
                    'is_tolerated'   => false,
                ],
            ],
        ]);
    }

    /**
     * State: uncertain verdict.
     */
    public function uncertain(): static
    {
        return $this->state([
            'confidence_score'       => 0.6500,
            'verdict'                => VerificationVerdict::UNCERTAIN,
            'hold_payment_capture'   => true,
        ]);
    }

    /**
     * State: require customer confirmation verdict.
     */
    public function requireConfirmation(): static
    {
        return $this->state([
            'confidence_score'       => 0.8500,
            'verdict'                => VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION,
            'hold_payment_capture'   => true,
        ]);
    }
}
