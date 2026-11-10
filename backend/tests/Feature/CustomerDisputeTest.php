<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ComplianceEventType;
use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Models\CustomerDispute;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDisputeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function makeMerchantUser(): User
    {
        return User::factory()->create(['role' => 'merchant']);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_customer_can_open_dispute_against_intent(): void
    {
        $customer = $this->makeCustomer();
        $merchant = Merchant::factory()->create(['compliance_score' => 100.00]);
        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
        ]);

        $payload = [
            'dispute_category'     => 'item_not_as_described',
            'description'          => 'The item received was defective and did not match product specification.',
            'claimed_amount_cents' => 9900,
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/disputes",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('purchase_intent_id', $intent->id)
            ->assertJsonPath('dispute_category', 'item_not_as_described')
            ->assertJsonPath('status', DisputeStatus::OPENED->value);

        // Dispute created
        $this->assertDatabaseHas('customer_disputes', [
            'purchase_intent_id' => $intent->id,
            'customer_id'        => $customer->id,
            'merchant_id'        => $merchant->id,
            'status'             => DisputeStatus::OPENED->value,
        ]);

        // Compliance event automatically registered against the merchant
        $this->assertDatabaseHas('merchant_compliance_events', [
            'merchant_id' => $merchant->id,
            'event_type'  => ComplianceEventType::DISPUTE_FILED->value,
        ]);

        // Merchant compliance score deducted by 5
        $this->assertSame(95.00, (float) $merchant->fresh()->compliance_score);
    }

    public function test_customer_can_list_their_disputes(): void
    {
        $customer = $this->makeCustomer();
        CustomerDispute::factory()->count(2)->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson('/api/v1/disputes');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_merchant_can_respond_to_dispute(): void
    {
        $merchantUser = $this->makeMerchantUser();
        $dispute = CustomerDispute::factory()->create([
            'status' => DisputeStatus::OPENED,
        ]);

        $response = $this->actingAs($merchantUser)->postJson(
            "/api/v1/disputes/{$dispute->id}/respond",
            [
                'merchant_response' => 'We apologize for the oversight. Replacement unit has been expedited.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('status', DisputeStatus::UNDER_MERCHANT_RESPONSE->value)
            ->assertJsonPath('merchant_response', 'We apologize for the oversight. Replacement unit has been expedited.');
    }

    public function test_admin_can_resolve_dispute_with_refund(): void
    {
        $admin = $this->makeAdmin();
        $dispute = CustomerDispute::factory()->underMerchantResponse()->create();

        $response = $this->actingAs($admin)->postJson(
            "/api/v1/disputes/{$dispute->id}/resolve",
            [
                'status' => DisputeStatus::RESOLVED_REFUNDED->value,
                'notes'  => 'Full refund approved after administrative inspection.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('status', DisputeStatus::RESOLVED_REFUNDED->value)
            ->assertJsonPath('resolution_notes', 'Full refund approved after administrative inspection.');

        $this->assertSame(DisputeStatus::RESOLVED_REFUNDED, $dispute->fresh()->status);
    }

    public function test_unauthenticated_user_cannot_access_disputes(): void
    {
        $response = $this->getJson('/api/v1/disputes');

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
