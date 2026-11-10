<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retrieve_platform_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Merchant::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/metrics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'fraud_prevention' => [
                        'interceptions_count',
                        'prevented_surge_amount_cents',
                        'prevented_surge_amount',
                    ],
                    'intent_analytics' => [
                        'total_intents',
                        'intents_by_status',
                    ],
                    'payment_analytics' => [
                        'total_authorizations',
                        'total_captured_amount_cents',
                        'total_captured_amount',
                        'held_authorizations_count',
                        'voided_authorizations_count',
                    ],
                    'merchant_health' => [
                        'total_merchants',
                        'average_compliance_score',
                        'merchants_by_status',
                    ],
                    'dispute_analytics' => [
                        'total_disputes',
                        'open_disputes',
                        'resolved_disputes',
                    ],
                    'audit_provenance' => [
                        'total_audit_records',
                    ],
                ],
            ]);
    }

    public function test_non_admin_is_forbidden_from_platform_metrics(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/v1/admin/metrics');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}
