<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MerchantAgreementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantAgreement extends Model
{
    /** @use HasFactory<MerchantAgreementFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'version',
        'title',
        'content_hash',
        'product_accuracy_policy',
        'shipping_delivery_policy',
        'substitution_policy',
        'refund_replacement_policy',
        'is_active',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Acceptances of this agreement.
     */
    public function acceptances(): HasMany
    {
        return $this->hasMany(MerchantAgreementAcceptance::class);
    }

    /**
     * Scope for active agreements.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->latest('published_at');
    }

    /**
     * Compute SHA-256 cryptographic digest of policies.
     */
    public static function computeDigest(
        string $productAccuracy,
        string $shippingDelivery,
        string $substitutions,
        string $refundReplacement,
    ): string {
        $canonical = implode("\n---\n", [
            trim($productAccuracy),
            trim($shippingDelivery),
            trim($substitutions),
            trim($refundReplacement),
        ]);

        return hash('sha256', $canonical);
    }
}
