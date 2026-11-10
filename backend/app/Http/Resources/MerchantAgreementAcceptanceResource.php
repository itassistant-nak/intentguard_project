<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MerchantAgreementAcceptance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MerchantAgreementAcceptance
 */
class MerchantAgreementAcceptanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant_agreement_id' => $this->merchant_agreement_id,
            'agreement_version' => $this->agreement?->version,
            'signatory_name' => $this->signatory_name,
            'signatory_title' => $this->signatory_title,
            'ip_address' => $this->ip_address,
            'terms_digest_snapshot' => $this->terms_digest_snapshot,
            'accepted_at' => $this->accepted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
