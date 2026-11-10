<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\MerchantPlatformType;
use App\Enums\MerchantStatus;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'intentguard_id',
        'business_name',
        'legal_name',
        'business_registration_number',
        'tax_id',
        'business_type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website_url',
        'platform_type',
        'status',
        'compliance_score',
        'verification_notes',
        'verified_at',
        'verified_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'platform_type' => MerchantPlatformType::class,
            'status' => MerchantStatus::class,
            'compliance_score' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Agreement acceptances submitted by this merchant.
     */
    public function agreementAcceptances(): HasMany
    {
        return $this->hasMany(MerchantAgreementAcceptance::class);
    }

    /**
     * Latest agreement acceptance.
     */
    public function latestAgreementAcceptance(): HasOne
    {
        return $this->hasOne(MerchantAgreementAcceptance::class)->latestOfMany();
    }

    /**
     * Audit log of all status transitions.
     */
    public function statusAuditLogs(): HasMany
    {
        return $this->hasMany(MerchantStatusAuditLog::class);
    }

    /**
     * User administrator who verified the merchant.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope to find merchant by public IntentGuard ID or internal ID.
     */
    public function scopeByIdOrIntentGuardId(Builder $query, string|int $identifier): Builder
    {
        if (is_numeric($identifier)) {
            return $query->where('id', (int) $identifier);
        }

        return $query->where('intentguard_id', $identifier);
    }

    /**
     * Determine if merchant has accepted the currently active agreement.
     */
    public function hasAcceptedAgreement(MerchantAgreement $agreement): bool
    {
        return $this->agreementAcceptances()
            ->where('merchant_agreement_id', $agreement->id)
            ->exists();
    }

    /**
     * Determine if the merchant is active and verified.
     */
    public function isVerified(): bool
    {
        return $this->status === MerchantStatus::VERIFIED;
    }
}
