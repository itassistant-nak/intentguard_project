<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PurchaseIntentStatus;
use App\Enums\VerificationVerdict;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function makeIntent(User $customer, Merchant $merchant): PurchaseIntent
    {
        return PurchaseIntent::factory()->create([
            'customer_id'               => $customer->id,
            'merchant_id'               => $merchant->id,
            'unit_price_cents'          => 29900,
            'currency'                  => 'USD',
            'quantity'                  => 1,
            'shipping_conditions_json'  => [
                'shipping_cost_cents' => 1500,
                'method'              => 'Express Delivery',
            ],
            'specification_json'        => [
                'brand'     => 'Bose',
                'model'     => 'QuietComfort 45',
                'condition' => 'new',
                'colour'    => 'Triple Black',
            ],
            'allowed_substitutions_json' => [
                'colour_variants' => false,
                'refurbished_ok'  => false,
            ],
            'status'                    => PurchaseIntentStatus::PENDING,
        ]);
    }

    public function test_customer_can_verify_checkout_and_receive_match_verdict(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($customer, $merchant);

        $payload = [
            'merchant_id'         => $merchant->id,
            'currency'            => 'USD',
            'unit_price_cents'    => 29900,
            'quantity'            => 1,
            'total_price_cents'   => 31400, // 29900 + 1500
            'shipping_cost_cents' => 1500,
            'specification_json'  => [
                'brand'     => 'Bose',
                'model'     => 'QuietComfort 45',
                'condition' => 'new',
                'colour'    => 'Triple Black',
            ],
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/verify",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('purchase_intent_id', $intent->id)
            ->assertJsonPath('verdict', VerificationVerdict::MATCH->value)
            ->assertJsonPath('confidence_score', 1)
            ->assertJsonPath('hold_payment_capture', false)
            ->assertJsonPath('discrepancy_count', 0);

        // Check intent status was updated to verified
        $this->assertSame(PurchaseIntentStatus::VERIFIED, $intent->fresh()->status);

        $this->assertDatabaseHas('verification_decisions', [
            'purchase_intent_id'   => $intent->id,
            'verdict'              => VerificationVerdict::MATCH->value,
            'hold_payment_capture' => false,
        ]);
    }

    public function test_price_surge_produces_mismatch_and_updates_intent_status(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($customer, $merchant);

        $payload = [
            'merchant_id'         => $merchant->id,
            'currency'            => 'USD',
            'unit_price_cents'    => 35000, // Surged from 29900 to 35000
            'quantity'            => 1,
            'total_price_cents'   => 36500,
            'shipping_cost_cents' => 1500,
            'specification_json'  => [
                'brand'     => 'Bose',
                'model'     => 'QuietComfort 45',
                'condition' => 'new',
                'colour'    => 'Triple Black',
            ],
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/verify",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('verdict', VerificationVerdict::MISMATCH->value)
            ->assertJsonPath('confidence_score', 0)
            ->assertJsonPath('hold_payment_capture', true)
            ->assertJsonPath('discrepancy_count', 1);

        // Check intent status was updated to mismatched
        $this->assertSame(PurchaseIntentStatus::MISMATCHED, $intent->fresh()->status);
    }

    public function test_disallowed_colour_variation_requires_customer_confirmation(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($customer, $merchant);

        $payload = [
            'merchant_id'         => $merchant->id,
            'currency'            => 'USD',
            'unit_price_cents'    => 29900,
            'quantity'            => 1,
            'total_price_cents'   => 31400,
            'shipping_cost_cents' => 1500,
            'specification_json'  => [
                'brand'     => 'Bose',
                'model'     => 'QuietComfort 45',
                'condition' => 'new',
                'colour'    => 'White Smoke', // Disallowed color change
            ],
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/verify",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('verdict', VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION->value)
            ->assertJsonPath('hold_payment_capture', true);
    }

    public function test_can_list_verification_decisions_for_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($customer, $merchant);

        VerificationDecision::factory()->count(2)->create([
            'purchase_intent_id' => $intent->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/intents/{$intent->id}/verifications"
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_view_single_verification_decision(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($customer, $merchant);

        $decision = VerificationDecision::factory()->create([
            'purchase_intent_id' => $intent->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/verifications/{$decision->id}"
        );

        $response->assertOk()
            ->assertJsonPath('id', $decision->id)
            ->assertJsonPath('purchase_intent_id', $intent->id);
    }

    public function test_customer_cannot_verify_someone_elses_intent(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $merchant = Merchant::factory()->create();
        $intent = $this->makeIntent($owner, $merchant);

        $response = $this->actingAs($stranger)->postJson(
            "/api/v1/intents/{$intent->id}/verify",
            [
                'merchant_id'       => $merchant->id,
                'currency'          => 'USD',
                'unit_price_cents'  => 29900,
                'quantity'          => 1,
                'total_price_cents' => 29900,
            ]
        );

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INTENT_NOT_FOUND');
    }

    public function test_unauthenticated_verification_requests_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/intents/1/verify', []);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
