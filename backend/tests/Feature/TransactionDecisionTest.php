<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DecisionResolutionStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\ResolutionAuditAction;
use App\Enums\VerificationVerdict;
use App\Models\DecisionResolutionAuditLog;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDecisionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function makePendingHoldDecision(User $customer): VerificationDecision
    {
        $merchant = Merchant::factory()->create();
        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'status'      => PurchaseIntentStatus::PENDING,
        ]);

        return VerificationDecision::factory()->requireConfirmation()->create([
            'purchase_intent_id'   => $intent->id,
            'hold_payment_capture' => true,
            'resolution_status'    => DecisionResolutionStatus::UNRESOLVED,
        ]);
    }

    public function test_customer_can_confirm_pending_hold_decision(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/verifications/{$decision->id}/confirm",
            [
                'notes' => 'I accept this substitution.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('id', $decision->id)
            ->assertJsonPath('resolution_status', DecisionResolutionStatus::CUSTOMER_CONFIRMED->value)
            ->assertJsonPath('hold_payment_capture', false)
            ->assertJsonPath('is_pending_hold', false);

        $this->assertDatabaseHas('verification_decisions', [
            'id'                   => $decision->id,
            'resolution_status'    => DecisionResolutionStatus::CUSTOMER_CONFIRMED->value,
            'hold_payment_capture' => false,
            'resolved_by'          => $customer->id,
        ]);

        $this->assertDatabaseHas('decision_resolution_audit_logs', [
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::CUSTOMER_CONFIRMED->value,
            'actor_id'                 => $customer->id,
        ]);
    }

    public function test_customer_can_reject_pending_hold_decision(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/verifications/{$decision->id}/reject",
            [
                'reason' => 'Substituted product specification is unacceptable.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('id', $decision->id)
            ->assertJsonPath('resolution_status', DecisionResolutionStatus::CUSTOMER_REJECTED->value)
            ->assertJsonPath('hold_payment_capture', true);

        $this->assertDatabaseHas('verification_decisions', [
            'id'                   => $decision->id,
            'resolution_status'    => DecisionResolutionStatus::CUSTOMER_REJECTED->value,
            'hold_payment_capture' => true,
        ]);
    }

    public function test_confirming_already_resolved_decision_returns_422(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        // First confirmation succeeds
        $this->actingAs($customer)->postJson("/api/v1/verifications/{$decision->id}/confirm");

        // Second confirmation fails
        $response = $this->actingAs($customer)->postJson(
            "/api/v1/verifications/{$decision->id}/confirm"
        );

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_RESOLUTION_STATE');
    }

    public function test_rejection_requires_mandatory_reason(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/verifications/{$decision->id}/reject",
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_can_view_resolution_audit_logs(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        DecisionResolutionAuditLog::create([
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::HOLD_PLACED,
            'actor_type'               => \App\Enums\ResolutionActorType::SYSTEM,
            'actor_id'                 => null,
            'reason'                   => 'Initial hold placed',
            'created_at'               => now(),
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/verifications/{$decision->id}/audit-log"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'hold_placed');
    }

    public function test_can_list_pending_holds_for_customer(): void
    {
        $customer = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($customer);

        $response = $this->actingAs($customer)->getJson(
            '/api/v1/verifications/pending-holds'
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $decision->id);
    }

    public function test_customer_cannot_confirm_another_customers_decision(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $decision = $this->makePendingHoldDecision($owner);

        $response = $this->actingAs($stranger)->postJson(
            "/api/v1/verifications/{$decision->id}/confirm"
        );

        $response->assertStatus(404)
            ->assertJsonPath('code', 'VERIFICATION_NOT_FOUND');
    }

    public function test_unauthenticated_resolution_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/verifications/1/confirm', []);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
