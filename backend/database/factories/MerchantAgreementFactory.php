<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchantAgreement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantAgreement>
 */
class MerchantAgreementFactory extends Factory
{
    protected $model = MerchantAgreement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accuracy = 'Merchants guarantee delivered products strictly match product listings in title, model, and condition.';
        $shipping = 'Merchants must dispatch items within 48 hours and provide valid carrier tracking.';
        $substitutions = 'Merchants shall not make any substitutions without verified customer consent.';
        $refund = 'Merchants guarantee a 14-day hassle-free replacement or refund for confirmed discrepancies.';

        $hash = MerchantAgreement::computeDigest($accuracy, $shipping, $substitutions, $refund);

        return [
            'version' => fake()->unique()->numerify('#.#.#'),
            'title' => 'IntentGuard Standard Merchant Agreement',
            'content_hash' => $hash,
            'product_accuracy_policy' => $accuracy,
            'shipping_delivery_policy' => $shipping,
            'substitution_policy' => $substitutions,
            'refund_replacement_policy' => $refund,
            'is_active' => true,
            'published_at' => now(),
        ];
    }
}
