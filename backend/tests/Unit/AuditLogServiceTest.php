<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SystemAuditEvent;
use App\Models\Merchant;
use App\Models\SystemAuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService();
    }

    public function test_record_creates_audit_log_with_cryptographic_hash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $merchant = Merchant::factory()->create();

        $log = $this->service->record(
            event:     SystemAuditEvent::MERCHANT_REGISTERED,
            auditable: $merchant,
            actor:     $admin,
            options:   ['metadata' => ['registration_source' => 'web_portal']]
        );

        $this->assertNotNull($log->id);
        $this->assertSame(SystemAuditEvent::MERCHANT_REGISTERED, $log->event);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertNull($log->previous_hash); // First entry in chain
        $this->assertNotEmpty($log->tamper_hash);
        $this->assertSame(64, strlen($log->tamper_hash)); // SHA-256 hex string
    }

    public function test_subsequent_records_form_a_linear_hash_chain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log1 = $this->service->record(
            event:   SystemAuditEvent::SECURITY_LOGIN,
            actor:   $admin,
            options: ['metadata' => ['session_id' => 'sess_1']]
        );

        $log2 = $this->service->record(
            event:   SystemAuditEvent::MERCHANT_STATUS_UPDATED,
            actor:   $admin,
            options: ['new_values' => ['status' => 'verified']]
        );

        $log3 = $this->service->record(
            event:   SystemAuditEvent::DISPUTE_RESOLVED,
            actor:   $admin,
            options: ['new_values' => ['status' => 'resolved_refunded']]
        );

        $this->assertNull($log1->previous_hash);
        $this->assertSame($log1->tamper_hash, $log2->previous_hash);
        $this->assertSame($log2->tamper_hash, $log3->previous_hash);
    }

    public function test_verify_chain_passes_for_untampered_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 5; $i++) {
            $this->service->record(
                event:   SystemAuditEvent::SECURITY_LOGIN,
                actor:   $admin,
                options: ['metadata' => ['iteration' => $i]]
            );
        }

        $result = $this->service->verifyChain();

        $this->assertTrue($result['is_intact']);
        $this->assertSame(5, $result['verified_count']);
        $this->assertNull($result['broken_record_id']);
    }

    public function test_verify_chain_detects_tampered_payload_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log1 = $this->service->record(
            event:   SystemAuditEvent::SECURITY_LOGIN,
            actor:   $admin
        );

        $log2 = $this->service->record(
            event:   SystemAuditEvent::MERCHANT_STATUS_UPDATED,
            actor:   $admin,
            options: ['new_values' => ['status' => 'verified']]
        );

        // Tamper directly with the database record of log2
        SystemAuditLog::where('id', $log2->id)->update([
            'new_values_json' => json_encode(['status' => 'suspended_tampered']),
        ]);

        $result = $this->service->verifyChain();

        $this->assertFalse($result['is_intact']);
        $this->assertSame(1, $result['verified_count']); // Log 1 was valid, log 2 broke
        $this->assertSame($log2->id, $result['broken_record_id']);
        $this->assertStringContainsString('Tampering detected', $result['message']);
    }

    public function test_verify_chain_detects_severed_hash_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log1 = $this->service->record(event: SystemAuditEvent::SECURITY_LOGIN, actor: $admin);
        $log2 = $this->service->record(event: SystemAuditEvent::MERCHANT_STATUS_UPDATED, actor: $admin);

        // Alter log2's previous_hash pointer
        SystemAuditLog::where('id', $log2->id)->update([
            'previous_hash' => 'corrupted_previous_hash_value_00000000000000000000000000000000',
        ]);

        $result = $this->service->verifyChain();

        $this->assertFalse($result['is_intact']);
        $this->assertSame(1, $result['verified_count']);
        $this->assertSame($log2->id, $result['broken_record_id']);
        $this->assertStringContainsString('Cryptographic chain severed', $result['message']);
    }
}
