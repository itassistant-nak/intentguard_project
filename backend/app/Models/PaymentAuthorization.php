<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentAuthorizationStatus;
use Database\Factories\PaymentAuthorizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAuthorization extends Model
{
    /** @use HasFactory<PaymentAuthorizationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_authorizations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_intent_id',
        'verification_decision_id',
        'gateway',
        'order_id',
        'authorization_id',
        'capture_id',
        'amount_cents',
        'currency',
        'status',
        'idempotency_key',
        'customer_payer_id',
        'metadata_json',
        'authorized_at',
        'captured_at',
        'voided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents'  => 'integer',
            'status'        => PaymentAuthorizationStatus::class,
            'metadata_json' => 'array',
            'authorized_at' => 'datetime',
            'captured_at'   => 'datetime',
            'voided_at'     => 'datetime',
        ];
    }

    /**
     * The purchase intent associated with this payment authorization.
     *
     * @return BelongsTo<PurchaseIntent, $this>
     */
    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class, 'purchase_intent_id');
    }

    /**
     * The verification decision associated with this payment authorization.
     *
     * @return BelongsTo<VerificationDecision, $this>
     */
    public function verificationDecision(): BelongsTo
    {
        return $this->belongsTo(VerificationDecision::class, 'verification_decision_id');
    }

    /**
     * Formatted decimal amount representation.
     */
    public function formattedAmount(): string
    {
        return number_format($this->amount_cents / 100, 2);
    }

    /**
     * Whether the payment authorization is captured.
     */
    public function isCaptured(): bool
    {
        return $this->status === PaymentAuthorizationStatus::CAPTURED;
    }

    /**
     * Whether payment capture is currently held pending confirmation.
     */
    public function isHeld(): bool
    {
        return $this->status === PaymentAuthorizationStatus::HELD;
    }

    /**
     * Whether the authorization has been voided.
     */
    public function isVoided(): bool
    {
        return $this->status === PaymentAuthorizationStatus::VOIDED;
    }
}
