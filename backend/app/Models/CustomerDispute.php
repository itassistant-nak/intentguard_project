<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use Database\Factories\CustomerDisputeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDispute extends Model
{
    /** @use HasFactory<CustomerDisputeFactory> */
    use HasFactory;

    protected $table = 'customer_disputes';

    protected $fillable = [
        'purchase_intent_id',
        'customer_id',
        'merchant_id',
        'dispute_category',
        'description',
        'claimed_amount_cents',
        'evidence_snapshot_id',
        'status',
        'merchant_response',
        'merchant_responded_at',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'dispute_category'      => DisputeCategory::class,
            'status'                => DisputeStatus::class,
            'claimed_amount_cents'  => 'integer',
            'merchant_responded_at' => 'datetime',
            'resolved_at'           => 'datetime',
        ];
    }

    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function evidenceSnapshot(): BelongsTo
    {
        return $this->belongsTo(PurchaseEvidence::class, 'evidence_snapshot_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
