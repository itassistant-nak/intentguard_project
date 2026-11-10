<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\SystemAuditEvent;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Records an immutable system audit log entry, chaining to the previous entry's hash.
     *
     * @param array<string, mixed> $options
     */
    public function record(
        SystemAuditEvent $event,
        ?Model $auditable = null,
        ?User $actor = null,
        array $options = [],
    ): SystemAuditLog {
        return DB::transaction(function () use ($event, $auditable, $actor, $options) {
            // Find most recent entry to establish cryptographic hash link
            $latestEntry = SystemAuditLog::latest('id')->lockForUpdate()->first();
            $previousHash = $latestEntry?->tamper_hash;

            $now = Carbon::now();
            $timestamp = (string) $now->unix();

            $actorType = $actor !== null
                ? ($actor->role ?? 'user')
                : (string) ($options['actor_type'] ?? 'system');

            $actorId = $actor?->id ?? ($options['actor_id'] ?? null);
            $actorEmail = $actor?->email ?? ($options['actor_email'] ?? null);

            $auditableType = $auditable !== null ? get_class($auditable) : ($options['auditable_type'] ?? null);
            $auditableId = $auditable?->getKey() ?? ($options['auditable_id'] ?? null);

            $oldValues = $options['old_values'] ?? null;
            $newValues = $options['new_values'] ?? null;
            $metadata = $options['metadata'] ?? null;

            $ipAddress = $options['ip_address'] ?? (app()->runningInConsole() ? '127.0.0.1' : request()?->ip());
            $userAgent = $options['user_agent'] ?? (app()->runningInConsole() ? 'CLI' : request()?->userAgent());

            $tamperHash = SystemAuditLog::computeDigest(
                previousHash:  $previousHash,
                event:         $event->value,
                actorType:     $actorType,
                actorId:       $actorId !== null ? (int) $actorId : null,
                auditableType: $auditableType,
                auditableId:   $auditableId !== null ? (int) $auditableId : null,
                oldValues:     $oldValues,
                newValues:     $newValues,
                metadata:      $metadata,
                timestamp:     $timestamp,
            );

            return SystemAuditLog::create([
                'auditable_type'  => $auditableType,
                'auditable_id'    => $auditableId,
                'event'           => $event,
                'actor_type'      => $actorType,
                'actor_id'        => $actorId,
                'actor_email'     => $actorEmail,
                'ip_address'      => $ipAddress,
                'user_agent'      => $userAgent,
                'old_values_json' => $oldValues,
                'new_values_json' => $newValues,
                'metadata_json'   => $metadata,
                'previous_hash'   => $previousHash,
                'tamper_hash'     => $tamperHash,
                'created_at'      => $now,
            ]);
        });
    }

    /**
     * Traverses the cryptographic hash chain and verifies mathematical integrity of all log records.
     *
     * @return array{is_intact: bool, verified_count: int, broken_record_id: int|null, message: string, latest_hash: string|null}
     */
    public function verifyChain(int $limit = 1000): array
    {
        $records = SystemAuditLog::orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            return [
                'is_intact'        => true,
                'verified_count'   => 0,
                'broken_record_id' => null,
                'message'          => 'Audit log is empty; genesis chain ready.',
                'latest_hash'      => null,
            ];
        }

        $expectedPreviousHash = null;
        $verifiedCount = 0;
        $latestHash = null;

        foreach ($records as $record) {
            // Check 1: Previous hash link must match the preceding record's tamper hash
            if ($record->previous_hash !== $expectedPreviousHash) {
                return [
                    'is_intact'        => false,
                    'verified_count'   => $verifiedCount,
                    'broken_record_id' => $record->id,
                    'message'          => "Cryptographic chain severed at record #{$record->id}: previous_hash does not match preceding record's digest.",
                    'latest_hash'      => $latestHash,
                ];
            }

            // Check 2: Recompute hash from record content and compare to tamper_hash
            $expectedHash = SystemAuditLog::computeDigest(
                previousHash:  $record->previous_hash,
                event:         $record->event->value,
                actorType:     $record->actor_type,
                actorId:       $record->actor_id,
                auditableType: $record->auditable_type,
                auditableId:   $record->auditable_id,
                oldValues:     $record->old_values_json,
                newValues:     $record->new_values_json,
                metadata:      $record->metadata_json,
                timestamp:     (string) $record->created_at->unix(),
            );

            if ($record->tamper_hash !== $expectedHash) {
                return [
                    'is_intact'        => false,
                    'verified_count'   => $verifiedCount,
                    'broken_record_id' => $record->id,
                    'message'          => "Tampering detected at record #{$record->id}: stored tamper_hash does not match recomputed payload digest.",
                    'latest_hash'      => $latestHash,
                ];
            }

            $expectedPreviousHash = $record->tamper_hash;
            $latestHash = $record->tamper_hash;
            $verifiedCount++;
        }

        return [
            'is_intact'        => true,
            'verified_count'   => $verifiedCount,
            'broken_record_id' => null,
            'message'          => "Audit chain verified intact across {$verifiedCount} records.",
            'latest_hash'      => $latestHash,
        ];
    }
}
