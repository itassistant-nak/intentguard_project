<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MerchantStatus;
use App\Enums\PurchaseIntentStatus;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\PurchaseIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseIntentTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function makeVerifiedMerchant(): Merchant
    {
        return Merchant::factory()->create(['status' => MerchantStatus::VERIFIED]);
    }

    private function validPayload(Merchant $merchant): array
    {
        return [
            'merchant_id'              => $merchant->id,
            'product_name'             => 'Apple iPhone 15 Pro 256GB',
            'product_sku'              => 'APPL-IP15P-256-BLK',
            'product_url'              => 'https://example.com/iphone-15-pro',
            'specification_json'       => [
                'brand'     => 'Apple',
                'model'     => 'iPhone 15 Pro',
                'condition' => 'new',
                'colour'    => 'Black Titanium',
                'storage'   => '256GB',
            ],
            'quantity'                 => 1,
            'unit_price_cents'         => 119900,   // $1,199.00
            'currency'                 => 'USD',
            'shipping_conditions_json' => [
                'method'              => 'Standard Shipping',
                'estimated_days_min'  => 3,
                'estimated_days_max'  => 7,
                'shipping_cost_cents' => 0,
            ],
            'allowed_substitutions_json' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Test: Record a new purchase intent
    // -------------------------------------------------------------------------

    public function test_customer_can_record_purchase_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        $response = $this->actingAs($customer)->postJson(
            '/api/v1/intents',
            $this->validPayload($merchant)
        );

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('product_name', 'Apple iPhone 15 Pro 256GB')
            ->assertJsonPath('unit_price_cents', 119900)
            ->assertJsonPath('total_price_cents', 119900)
            ->assertJsonPath('currency', 'USD')
            ->assertJsonStructure([
                'id',
                'intent_hash',
                'status',
                'status_label',
                'merchant_id',
                'product_name',
                'unit_price',
                'total_price',
                'expires_at',
            ]);

        $this->assertDatabaseHas('purchase_intents', [
            'customer_id'  => $customer->id,
            'merchant_id'  => $merchant->id,
            'product_name' => 'Apple iPhone 15 Pro 256GB',
            'status'       => PurchaseIntentStatus::PENDING->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test: Validation fails on missing required fields
    // -------------------------------------------------------------------------

    public function test_recording_intent_fails_without_required_fields(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer)->postJson('/api/v1/intents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'merchant_id',
                'product_name',
                'specification_json',
                'quantity',
                'unit_price_cents',
                'currency',
                'shipping_conditions_json',
            ]);
    }

    // -------------------------------------------------------------------------
    // Test: Retrieve an intent by ID
    // -------------------------------------------------------------------------

    public function test_can_retrieve_intent_by_id(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'status'      => PurchaseIntentStatus::PENDING,
        ]);

        $response = $this->actingAs($customer)->getJson("/api/v1/intents/{$intent->id}");

        $response->assertOk()
            ->assertJsonPath('id', $intent->id)
            ->assertJsonPath('intent_hash', $intent->intent_hash);
    }

    // -------------------------------------------------------------------------
    // Test: Retrieve an intent by hash
    // -------------------------------------------------------------------------

    public function test_can_retrieve_intent_by_hash(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($customer)->getJson("/api/v1/intents/{$intent->intent_hash}");

        $response->assertOk()
            ->assertJsonPath('id', $intent->id);
    }

    // -------------------------------------------------------------------------
    // Test: Intent not found returns 404
    // -------------------------------------------------------------------------

    public function test_retrieving_nonexistent_intent_returns_404(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer)->getJson('/api/v1/intents/999999');

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INTENT_NOT_FOUND');
    }

    // -------------------------------------------------------------------------
    // Test: Customer can cancel a pending intent
    // -------------------------------------------------------------------------

    public function test_customer_can_cancel_pending_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'status'      => PurchaseIntentStatus::PENDING,
        ]);

        $response = $this->actingAs($customer)->postJson("/api/v1/intents/{$intent->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('status', PurchaseIntentStatus::CANCELLED->value);

        $this->assertDatabaseHas('purchase_intents', [
            'id'     => $intent->id,
            'status' => PurchaseIntentStatus::CANCELLED->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test: Cannot cancel an already-terminal intent
    // -------------------------------------------------------------------------

    public function test_cannot_cancel_already_terminal_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        $intent = PurchaseIntent::factory()->cancelled()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($customer)->postJson("/api/v1/intents/{$intent->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INTENT_TERMINAL');
    }

    // -------------------------------------------------------------------------
    // Test: Cryptographic integrity verification
    // -------------------------------------------------------------------------

    public function test_integrity_verification_passes_for_unmodified_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = $this->makeVerifiedMerchant();

        // Record via service to get a properly hashed intent
        $service = app(PurchaseIntentService::class);
        $intent  = $service->record($customer, $merchant, $this->validPayload($merchant));

        $response = $this->actingAs($customer)->getJson("/api/v1/intents/{$intent->id}/verify-integrity");

        $response->assertOk()
            ->assertJsonPath('integrity_ok', true);
    }

    // -------------------------------------------------------------------------
    // Test: Unauthenticated access is rejected
    // -------------------------------------------------------------------------

    public function test_unauthenticated_access_to_intents_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/intents', []);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    // -------------------------------------------------------------------------
    // Test: Price helpers return correct decimal representations
    // -------------------------------------------------------------------------

    public function test_price_helpers_return_correct_decimal_values(): void
    {
        $intent = new PurchaseIntent([
            'unit_price_cents' => 9999,
            'quantity'         => 3,
        ]);

        $this->assertSame('99.99', $intent->unitPriceDecimal());
        $this->assertSame('299.97', $intent->totalPriceDecimal());
        $this->assertSame(29997, $intent->totalPriceCents());
    }
}
