<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SystemAuditEvent;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemAuditLog>
 */
class SystemAuditLogFactory extends Factory
{
    protected $model = SystemAuditLog::class;

    public function definition(): array
    {
        $timestamp = (string) now()->unix();
        $event = SystemAuditEvent::MERCHANT_REGISTERED;

        $tamperHash = SystemAuditLog::computeDigest(
            previousHash:  null,
            event:         $event->value,
            actorType:     'admin',
            actorId:       1,
            auditableType: null,
            auditableId:   null,
            oldValues:     null,
            newValues:     ['status' => 'pending_review'],
            metadata:      null,
            timestamp:     $timestamp,
        );

        return [
            'auditable_type'  => null,
            'auditable_id'    => null,
            'event'           => $event,
            'actor_type'      => 'admin',
            'actor_id'        => User::factory(),
            'actor_email'     => fake()->safeEmail(),
            'ip_address'      => fake()->ipv4(),
            'user_agent'      => 'Mozilla/5.0 (Mock Agent)',
            'old_values_json' => null,
            'new_values_json' => ['status' => 'pending_review'],
            'metadata_json'   => null,
            'previous_hash'   => null,
            'tamper_hash'     => $tamperHash,
            'created_at'      => now(),
        ];
    }
}
