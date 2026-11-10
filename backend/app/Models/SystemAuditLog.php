<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemAuditEvent;
use Database\Factories\SystemAuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemAuditLog extends Model
{
    /** @use HasFactory<SystemAuditLogFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_audit_logs';

    /**
     * Immutable append-only log: disable updated_at timestamp.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'actor_type',
        'actor_id',
        'actor_email',
        'ip_address',
        'user_agent',
        'old_values_json',
        'new_values_json',
        'metadata_json',
        'previous_hash',
        'tamper_hash',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event'           => SystemAuditEvent::class,
            'old_values_json' => 'array',
            'new_values_json' => 'array',
            'metadata_json'   => 'array',
            'created_at'      => 'datetime',
        ];
    }

    /**
     * The polymorphic entity being audited.
     *
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user acting on this record, if known.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Computes the deterministic SHA-256 tamper hash for this audit record.
     */
    public static function computeDigest(
        ?string $previousHash,
        string $event,
        string $actorType,
        ?int $actorId,
        ?string $auditableType,
        ?int $auditableId,
        ?array $oldValues,
        ?array $newValues,
        ?array $metadata,
        string|int $timestamp,
    ): string {
        $canonicalPayload = [
            'previous_hash'  => $previousHash ?? 'GENESIS_BLOCK_000000000000000000000000000000000000000000000000000000',
            'event'          => $event,
            'actor_type'     => $actorType,
            'actor_id'       => $actorId,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'metadata'       => $metadata,
            'timestamp'      => (string) $timestamp,
        ];

        return hash('sha256', json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
