<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CustomerDispute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerDispute */
class CustomerDisputeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'purchase_intent_id'     => $this->purchase_intent_id,
            'customer_id'            => $this->customer_id,
            'customer_name'          => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'merchant_id'            => $this->merchant_id,
            'merchant_name'          => $this->whenLoaded('merchant', fn () => $this->merchant?->business_name),
            'dispute_category'       => $this->dispute_category->value,
            'dispute_category_label' => $this->dispute_category->label(),
            'description'            => $this->description,
            'claimed_amount_cents'   => $this->claimed_amount_cents,
            'claimed_amount'         => $this->claimed_amount_cents !== null ? number_format($this->claimed_amount_cents / 100, 2) : null,
            'evidence_snapshot_id'   => $this->evidence_snapshot_id,
            'status'                 => $this->status->value,
            'status_label'           => $this->status->label(),
            'is_terminal'            => $this->isTerminal(),
            'merchant_response'      => $this->merchant_response,
            'merchant_responded_at'  => $this->merchant_responded_at?->toISOString(),
            'resolution_notes'       => $this->resolution_notes,
            'resolved_at'            => $this->resolved_at?->toISOString(),
            'resolved_by'            => $this->resolved_by,
            'resolver_name'          => $this->whenLoaded('resolver', fn () => $this->resolver?->name),
            'created_at'             => $this->created_at->toISOString(),
            'updated_at'             => $this->updated_at->toISOString(),
        ];
    }
}
