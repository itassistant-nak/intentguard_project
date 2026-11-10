<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantAgreementAcceptance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'merchant_id',
        'merchant_agreement_id',
        'signatory_name',
        'signatory_title',
        'ip_address',
        'user_agent',
        'terms_digest_snapshot',
        'accepted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Merchant who accepted the agreement.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * The specific agreement accepted.
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(MerchantAgreement::class, 'merchant_agreement_id');
    }
}
