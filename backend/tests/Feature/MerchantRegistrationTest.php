<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BusinessType;
use App\Enums\MerchantPlatformType;
use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_merchant_successfully(): void
    {
        $payload = [
            'business_name' => 'Apex Electronics Ltd',
            'legal_name' => 'Apex Electronics Limited',
            'business_registration_number' => 'UK-REG-98765432',
            'tax_id' => 'GB987654321',
            'business_type' => BusinessType::CORPORATION->value,
            'contact_name' => 'Sarah Jenkins',
            'contact_email' => 'sarah@apexelectronics.co.uk',
            'contact_phone' => '+44 20 7946 0912',
            'website_url' => 'https://apexelectronics.co.uk',
            'platform_type' => MerchantPlatformType::SHOPIFY->value,
        ];

        $response = $this->postJson('/api/v1/merchants/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_name', 'Apex Electronics Ltd')
            ->assertJsonPath('data.status', MerchantStatus::PENDING_REVIEW->value)
            ->assertJsonPath('data.compliance_score', 100)
            ->assertJsonPath('data.agreement_accepted', false);

        $intentguardId = $response->json('data.intentguard_id');
        $this->assertMatchesRegularExpression('/^IG-MCH-[0-9A-F]{8}$/', $intentguardId);

        $this->assertDatabaseHas('merchants', [
            'intentguard_id' => $intentguardId,
            'contact_email' => 'sarah@apexelectronics.co.uk',
            'business_registration_number' => 'UK-REG-98765432',
        ]);
    }

    public function test_registration_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/merchants/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'business_name',
                'business_registration_number',
                'business_type',
                'contact_name',
                'contact_email',
                'contact_phone',
                'website_url',
                'platform_type',
            ]);
    }

    public function test_registration_fails_on_duplicate_email_or_registration_number(): void
    {
        Merchant::factory()->create([
            'contact_email' => 'existing@store.com',
            'business_registration_number' => 'DUPLICATE-123',
        ]);

        $payload = [
            'business_name' => 'Duplicate Test Store',
            'business_registration_number' => 'DUPLICATE-123',
            'business_type' => BusinessType::LLC->value,
            'contact_name' => 'John Doe',
            'contact_email' => 'existing@store.com',
            'contact_phone' => '+1 555 123 4567',
            'website_url' => 'https://duplicate-store.com',
            'platform_type' => MerchantPlatformType::WOOCOMMERCE->value,
        ];

        $response = $this->postJson('/api/v1/merchants/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'contact_email',
                'business_registration_number',
            ]);
    }

    public function test_can_retrieve_merchant_by_intentguard_id_or_id(): void
    {
        $merchant = Merchant::factory()->create([
            'business_name' => 'Lookup Target Store',
        ]);

        // Query by IntentGuard ID string
        $responseByIgId = $this->getJson("/api/v1/merchants/{$merchant->intentguard_id}");
        $responseByIgId->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_name', 'Lookup Target Store')
            ->assertJsonPath('data.intentguard_id', $merchant->intentguard_id);

        // Query by numeric internal ID
        $responseById = $this->getJson("/api/v1/merchants/{$merchant->id}");
        $responseById->assertStatus(200)
            ->assertJsonPath('data.intentguard_id', $merchant->intentguard_id);
    }
}
