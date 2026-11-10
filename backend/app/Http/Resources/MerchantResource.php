<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Merchant;
use App\Models\MerchantAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Merchant
 */
class MerchantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeAgreement = MerchantAgreement::active()->first();

        return [
            'id' => $this->id,
            'intentguard_id' => $this->intentguard_id,
            'business_name' => $this->business_name,
            'legal_name' => $this->legal_name,
            'business_registration_number' => $this->business_registration_number,
            'tax_id' => $this->tax_id,
            'business_type' => $this->business_type?->value ?? (string) $this->business_type,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'website_url' => $this->website_url,
            'platform_type' => $this->platform_type?->value ?? (string) $this->platform_type,
            'status' => $this->status?->value ?? (string) $this->status,
            'compliance_score' => (float) $this->compliance_score,
            'agreement_accepted' => $activeAgreement !== null && $this->hasAcceptedAgreement($activeAgreement),
            'verification_notes' => $this->verification_notes,
            'verified_at' => $this->verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
