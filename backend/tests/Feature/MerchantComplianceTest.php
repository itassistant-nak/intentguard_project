<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ComplianceAppealStatus;
use App\Enums\ComplianceEventType;
use App\Models\Merchant;
use App\Models\MerchantComplianceEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeMerchantUser(): User
    {
        return User::factory()->create(['role' => 'merchant']);
    }

    public function test_can_list_compliance_events_for_merchant(): void
    {
        $admin = $this->makeAdmin();
        $merchant = Merchant::factory()->create();

        MerchantComplianceEvent::factory()->count(2)->create([
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($admin)->getJson(
            "/api/v1/merchants/{$merchant->id}/compliance/events"
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_record_compliance_event(): void
    {
        $admin = $this->makeAdmin();
        $merchant = Merchant::factory()->create(['compliance_score' => 100.00]);

        $payload = [
            'event_type'  => 'mismatch_confirmed',
            'notes'       => 'Audited shipping variance against product page snapshot.',
        ];

        $response = $this->actingAs($admin)->postJson(
            "/api/v1/merchants/{$merchant->id}/compliance/events",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('event_type', 'mismatch_confirmed')
            ->assertJsonPath('score_delta', -15)
            ->assertJsonPath('score_after', 85);

        $this->assertDatabaseHas('merchant_compliance_events', [
            'merchant_id' => $merchant->id,
            'event_type'  => ComplianceEventType::MISMATCH_CONFIRMED->value,
        ]);
    }

    public function test_non_admin_cannot_record_compliance_event(): void
    {
        $user = $this->makeMerchantUser();
        $merchant = Merchant::factory()->create();

        $response = $this->actingAs($user)->postJson(
            "/api/v1/merchants/{$merchant->id}/compliance/events",
            ['event_type' => 'mismatch_confirmed']
        );

        $response->assertStatus(403);
    }

    public function test_merchant_can_submit_appeal(): void
    {
        $user = $this->makeMerchantUser();
        $merchant = Merchant::factory()->create();

        $event = MerchantComplianceEvent::factory()->create([
            'merchant_id'   => $merchant->id,
            'appeal_status' => ComplianceAppealStatus::NONE,
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/v1/compliance/events/{$event->id}/appeal",
            [
                'reason' => 'Customer authorized replacement item via support chat.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('appeal_status', ComplianceAppealStatus::PENDING_REVIEW->value);
    }

    public function test_admin_can_adjudicate_appeal(): void
    {
        $admin = $this->makeAdmin();
        $merchant = Merchant::factory()->create(['compliance_score' => 85.00]);

        $event = MerchantComplianceEvent::factory()->appealPending()->create([
            'merchant_id' => $merchant->id,
            'score_delta' => -15.00,
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/api/v1/compliance/events/{$event->id}/adjudicate",
            [
                'approved' => true,
                'notes'    => 'Chat evidence confirmed valid authorization.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('appeal_status', ComplianceAppealStatus::APPROVED->value);

        // Merchant score restored to 100
        $this->assertSame(100.00, (float) $merchant->fresh()->compliance_score);
    }
}
