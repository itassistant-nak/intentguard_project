<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\MerchantAgreement;
use App\Models\MerchantAgreementAcceptance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transition_merchant_status_to_under_review(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = Merchant::factory()->create([
            'status' => MerchantStatus::PENDING_REVIEW,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/merchants/{$merchant->intentguard_id}/status", [
                'status' => MerchantStatus::UNDER_REVIEW->value,
                'reason' => 'Admin began document and domain verification checks.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', MerchantStatus::UNDER_REVIEW->value);

        $this->assertDatabaseHas('merchant_status_audit_logs', [
            'merchant_id' => $merchant->id,
            'previous_status' => MerchantStatus::PENDING_REVIEW->value,
            'new_status' => MerchantStatus::UNDER_REVIEW->value,
            'actioned_by' => $admin->id,
        ]);
    }

    public function test_cannot_verify_merchant_if_active_agreement_not_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        MerchantAgreement::factory()->create(['is_active' => true]);

        $merchant = Merchant::factory()->create([
            'status' => MerchantStatus::UNDER_REVIEW,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/merchants/{$merchant->intentguard_id}/status", [
                'status' => MerchantStatus::VERIFIED->value,
                'reason' => 'Attempting verification before contract signature.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('merchants', [
            'id' => $merchant->id,
            'status' => MerchantStatus::UNDER_REVIEW->value,
        ]);
    }

    public function test_can_verify_merchant_once_active_agreement_is_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $agreement = MerchantAgreement::factory()->create(['is_active' => true]);

        $merchant = Merchant::factory()->create([
            'status' => MerchantStatus::UNDER_REVIEW,
        ]);

        // Record accepted agreement
        MerchantAgreementAcceptance::create([
            'merchant_id' => $merchant->id,
            'merchant_agreement_id' => $agreement->id,
            'signatory_name' => 'Managing Director',
            'signatory_title' => 'CEO',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'IntentGuard-Client/1.0',
            'terms_digest_snapshot' => $agreement->content_hash,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/merchants/{$merchant->intentguard_id}/status", [
                'status' => MerchantStatus::VERIFIED->value,
                'reason' => 'Business registry validated, KYC checks passed, agreement accepted.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', MerchantStatus::VERIFIED->value)
            ->assertJsonPath('data.agreement_accepted', true);

        $this->assertDatabaseHas('merchants', [
            'id' => $merchant->id,
            'status' => MerchantStatus::VERIFIED->value,
            'verified_by' => $admin->id,
        ]);

        // Audit log verified
        $auditResponse = $this->getJson("/api/v1/merchants/{$merchant->id}/audit-log");
        $auditResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }
}
