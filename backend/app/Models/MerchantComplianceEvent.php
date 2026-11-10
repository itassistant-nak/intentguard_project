<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplianceAppealStatus;
use App\Enums\ComplianceEventType;
use Database\Factories\MerchantComplianceEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantComplianceEvent extends Model
{
    /** @use HasFactory<MerchantComplianceEventFactory> */
    use HasFactory;

    /**
     * Disable updated_at — compliance events are append-only audit records.
     */
    public const UPDATED_AT = null;

    protected $table = 'merchant_compliance_events';

    protected $fillable = [
        'merchant_id',
        'purchase_intent_id',
        'verification_decision_id',
        'event_type',
        'severity',
        'score_delta',
        'score_before',
        'score_after',
        'notes',
        'evidence_reference',
        'appeal_status',
        'appeal_reason',
        'appeal_evidence_url',
        'appealed_at',
        'adjudicated_at',
        'adjudicated_by',
        'adjudication_notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type'     => ComplianceEventType::class,
            'appeal_status'  => ComplianceAppealStatus::class,
            'score_delta'    => 'float',
            'score_before'   => 'float',
            'score_after'    => 'float',
            'appealed_at'    => 'datetime',
            'adjudicated_at' => 'datetime',
            'created_at'     => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class);
    }

    public function verificationDecision(): BelongsTo
    {
        return $this->belongsTo(VerificationDecision::class);
    }

    public function adjudicator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjudicated_by');
    }

    public function isPenalty(): bool
    {
        return $this->score_delta < 0;
    }

    public function isAppealed(): bool
    {
        return $this->appeal_status === ComplianceAppealStatus::PENDING_REVIEW;
    }
}
