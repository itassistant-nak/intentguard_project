<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SystemAuditEvent;
use App\Models\SystemAuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditLogService();
    }

    public function test_admin_can_view_system_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->auditService->record(
            event:   SystemAuditEvent::MERCHANT_REGISTERED,
            actor:   $admin,
            options: ['new_values' => ['business_name' => 'Acme Inc']]
        );

        $this->auditService->record(
            event:   SystemAuditEvent::SECURITY_LOGIN,
            actor:   $admin
        );

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'event',
                        'event_label',
                        'actor_type',
                        'actor_id',
                        'tamper_hash',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_admin_can_filter_audit_logs_by_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->auditService->record(event: SystemAuditEvent::MERCHANT_REGISTERED, actor: $admin);
        $this->auditService->record(event: SystemAuditEvent::SECURITY_LOGIN, actor: $admin);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/audit-logs?event=security.login');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', 'security.login');
    }

    public function test_admin_can_verify_audit_chain_integrity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->auditService->record(event: SystemAuditEvent::MERCHANT_REGISTERED, actor: $admin);
        $this->auditService->record(event: SystemAuditEvent::SECURITY_LOGIN, actor: $admin);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/audit-logs/verify-chain');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_intact', true)
            ->assertJsonPath('data.verified_count', 2);
    }

    public function test_non_admin_is_forbidden_from_audit_logs(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/v1/admin/audit-logs');
        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_security_headers_are_enforced_on_api_responses(): void
    {
        $response = $this->getJson('/api/v1/agreements/active');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
