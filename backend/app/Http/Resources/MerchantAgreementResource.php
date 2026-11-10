<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MerchantAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MerchantAgreement
 */
class MerchantAgreementResource extends JsonResource
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
            'version' => $this->version,
            'title' => $this->title,
            'content_hash' => $this->content_hash,
            'policies' => [
                'product_accuracy' => $this->product_accuracy_policy,
                'shipping_delivery' => $this->shipping_delivery_policy,
                'substitution' => $this->substitution_policy,
                'refund_replacement' => $this->refund_replacement_policy,
            ],
            'is_active' => $this->is_active,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
