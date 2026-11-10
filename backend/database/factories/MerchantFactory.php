<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Enums\MerchantPlatformType;
use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hex = strtoupper(bin2hex(random_bytes(4)));

        return [
            'intentguard_id' => "IG-MCH-{$hex}",
            'business_name' => fake()->company(),
            'legal_name' => fake()->company().' Ltd',
            'business_registration_number' => 'REG-'.fake()->unique()->numerify('########'),
            'tax_id' => 'TAX-'.fake()->numerify('#########'),
            'business_type' => fake()->randomElement(BusinessType::cases()),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'website_url' => fake()->url(),
            'platform_type' => fake()->randomElement(MerchantPlatformType::cases()),
            'status' => MerchantStatus::PENDING_REVIEW,
            'compliance_score' => 100.00,
            'verification_notes' => null,
            'verified_at' => null,
            'verified_by' => null,
        ];
    }

    /**
     * Indicate that the merchant is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantStatus::VERIFIED,
            'verified_at' => now(),
        ]);
    }
}
