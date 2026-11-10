<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseIntentStatus;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseIntent>
 */
class PurchaseIntentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PurchaseIntent>
     */
    protected $model = PurchaseIntent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPriceCents = $this->faker->numberBetween(999, 199999); // $9.99–$1999.99

        return [
            'customer_id'               => User::factory(),
            'merchant_id'               => Merchant::factory(),
            'product_name'              => $this->faker->words(3, true),
            'product_sku'               => strtoupper($this->faker->bothify('SKU-####-??')),
            'product_url'               => $this->faker->url(),
            'specification_json'        => [
                'brand'     => $this->faker->company(),
                'model'     => $this->faker->word(),
                'condition' => 'new',
                'colour'    => $this->faker->safeColorName(),
            ],
            'quantity'                  => $this->faker->numberBetween(1, 5),
            'unit_price_cents'          => $unitPriceCents,
            'currency'                  => 'USD',
            'shipping_conditions_json'  => [
                'method'                => 'Standard Shipping',
                'estimated_days_min'    => 3,
                'estimated_days_max'    => 7,
                'shipping_cost_cents'   => 599,
            ],
            'allowed_substitutions_json'=> null,
            'intent_hash'               => hash('sha256', $this->faker->unique()->uuid()),
            'status'                    => PurchaseIntentStatus::PENDING,
            'expires_at'                => now()->addHours(24),
        ];
    }

    /**
     * State: set status to confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(['status' => PurchaseIntentStatus::CONFIRMED]);
    }

    /**
     * State: set status to verified (MATCH result).
     */
    public function verified(): static
    {
        return $this->state(['status' => PurchaseIntentStatus::VERIFIED]);
    }

    /**
     * State: set status to mismatched.
     */
    public function mismatched(): static
    {
        return $this->state(['status' => PurchaseIntentStatus::MISMATCHED]);
    }

    /**
     * State: set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(['status' => PurchaseIntentStatus::CANCELLED]);
    }

    /**
     * State: set status to expired with a past expires_at.
     */
    public function expired(): static
    {
        return $this->state([
            'status'     => PurchaseIntentStatus::EXPIRED,
            'expires_at' => now()->subHour(),
        ]);
    }
}
