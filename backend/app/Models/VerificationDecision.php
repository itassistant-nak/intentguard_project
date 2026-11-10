<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationVerdict;
use Database\Factories\VerificationDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'decided_at',
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
}
