<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseIntentStatus;
use Database\Factories\PurchaseIntentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseIntent extends Model
{
    /** @use HasFactory<PurchaseIntentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'merchant_id',
        'product_name',
        'product_sku',
        'product_url',
        'specification_json',
        'quantity',
        'unit_price_cents',
        'currency',
        'shipping_conditions_json',
        'allowed_substitutions_json',
        'intent_hash',
        'status',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specification_json'         => 'array',
            'shipping_conditions_json'   => 'array',
            'allowed_substitutions_json' => 'array',
            'status'                     => PurchaseIntentStatus::class,
            'unit_price_cents'           => 'integer',
            'quantity'                   => 'integer',
            'expires_at'                 => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The customer (user) who recorded this intent.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * The merchant this intent is associated with.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Evidence records forming the provenance chain for this intent.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(PurchaseEvidence::class)->orderBy('captured_at');
    }

    /**
     * Verification decisions performed on this intent.
     */
    public function verificationDecisions(): HasMany
    {
        return $this->hasMany(VerificationDecision::class)->latest('decided_at');
    }

    /**
     * Most recent verification decision.
     */
    public function latestVerificationDecision(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VerificationDecision::class)->latestOfMany('decided_at');
    }

    /**
     * Payment authorizations associated with this intent.
     */
    public function paymentAuthorizations(): HasMany
    {
        return $this->hasMany(PaymentAuthorization::class)->latest();
    }

    /**
     * Most recent payment authorization.
     */
    public function latestPaymentAuthorization(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentAuthorization::class)->latestOfMany();
    }

    // -------------------------------------------------------------------------
    // Business logic helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the total price in cents (unit_price_cents × quantity).
     */
    public function totalPriceCents(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }

    /**
     * Returns the unit price as a human-readable decimal string.
     * e.g. 9999 cents → "99.99"
     */
    public function unitPriceDecimal(): string
    {
        return number_format($this->unit_price_cents / 100, 2, '.', '');
    }

    /**
     * Returns the total price as a human-readable decimal string.
     */
    public function totalPriceDecimal(): string
    {
        return number_format($this->totalPriceCents() / 100, 2, '.', '');
    }

    /**
     * Whether this intent is still open/mutable.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Whether the intent has passed its expiry time.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
