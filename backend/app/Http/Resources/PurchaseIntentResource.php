<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PurchaseIntent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseIntent */
class PurchaseIntentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'intent_hash'                => $this->intent_hash,
            'status'                     => $this->status->value,
            'status_label'               => $this->status->label(),

            // Parties
            'customer_id'                => $this->customer_id,
            'merchant_id'                => $this->merchant_id,
            'merchant_intentguard_id'    => $this->whenLoaded('merchant', fn () => $this->merchant->intentguard_id),
            'merchant_name'              => $this->whenLoaded('merchant', fn () => $this->merchant->business_name),

            // Product identity
            'product_name'               => $this->product_name,
            'product_sku'                => $this->product_sku,
            'product_url'                => $this->product_url,
            'specification'              => $this->specification_json,

            // Pricing (expose both cents and human-readable)
            'quantity'                   => $this->quantity,
            'unit_price_cents'           => $this->unit_price_cents,
            'unit_price'                 => $this->unitPriceDecimal(),
            'total_price_cents'          => $this->totalPriceCents(),
            'total_price'                => $this->totalPriceDecimal(),
            'currency'                   => $this->currency,

            // Conditions
            'shipping_conditions'        => $this->shipping_conditions_json,
            'allowed_substitutions'      => $this->allowed_substitutions_json,

            // Timing
            'expires_at'                 => $this->expires_at?->toISOString(),
            'created_at'                 => $this->created_at->toISOString(),
            'updated_at'                 => $this->updated_at->toISOString(),
        ];
    }
}
