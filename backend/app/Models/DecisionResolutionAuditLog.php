<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResolutionAction;
use App\Enums\ResolutionActorType;
use App\Enums\ResolutionAuditAction;
use Database\Factories\DecisionResolutionAuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionResolutionAuditLog extends Model
{
    /** @use HasFactory<DecisionResolutionAuditLogFactory> */
    use HasFactory;

    /**
     * Disable updated_at — audit logs are strictly append-only.
     */
    public const UPDATED_AT = null;

    protected $table = 'decision_resolution_audit_logs';

    protected $fillable = [
        'verification_decision_id',
        'action',
        'actor_type',
        'actor_id',
        'reason',
        'metadata_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action'        => ResolutionAuditAction::class,
            'actor_type'    => ResolutionActorType::class,
            'metadata_json' => 'array',
            'created_at'    => 'datetime',
        ];
    }

    public function verificationDecision(): BelongsTo
    {
        return $this->belongsTo(VerificationDecision::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
