<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DecisionResolutionStatus;
use App\Enums\VerificationVerdict;
use Database\Factories\VerificationDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationDecision extends Model
{
    /** @use HasFactory<VerificationDecisionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'verification_decisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_intent_id',
        'checkout_payload_json',
        'discrepancy_flags_json',
        'confidence_score',
        'verdict',
        'hold_payment_capture',
        'resolution_status',
        'resolved_at',
        'resolved_by',
        'decided_at',
    ];

    /**
     * Default model attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'resolution_status' => DecisionResolutionStatus::UNRESOLVED,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checkout_payload_json' => 'array',
            'discrepancy_flags_json' => 'array',
            'confidence_score'      => 'float',
            'verdict'               => VerificationVerdict::class,
            'hold_payment_capture'  => 'boolean',
            'resolution_status'     => DecisionResolutionStatus::class,
            'resolved_at'           => 'datetime',
            'decided_at'            => 'datetime',
        ];
    }

    /**
     * The purchase intent evaluated by this decision.
     */
    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class);
    }

    /**
     * The user (customer or admin) who resolved the decision hold.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Append-only resolution audit history.
     */
    public function resolutionAuditLogs(): HasMany
    {
        return $this->hasMany(DecisionResolutionAuditLog::class)->orderBy('created_at');
    }

    /**
     * Check if verdict was a clean MATCH.
     */
    public function isMatch(): bool
    {
        return $this->verdict === VerificationVerdict::MATCH;
    }

    /**
     * Check if capture was held or blocked.
     */
    public function isCaptureHeld(): bool
    {
        return $this->hold_payment_capture;
    }

    /**
     * Check if decision is currently in an unresolved hold status.
     */
    public function isPendingHold(): bool
    {
        return $this->hold_payment_capture
            && $this->resolution_status === DecisionResolutionStatus::UNRESOLVED
            && in_array($this->verdict, [
                VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION,
                VerificationVerdict::UNCERTAIN,
            ], true);
    }

    /**
     * Check if this decision has been resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolution_status->isResolved();
    }
}
