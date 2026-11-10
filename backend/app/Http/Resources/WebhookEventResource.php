<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebhookEvent */
class WebhookEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'gateway'            => $this->gateway,
            'event_id'           => $this->event_id,
            'event_type'         => $this->event_type,
            'resource_type'      => $this->resource_type,
            'resource_id'        => $this->resource_id,
            'signature_verified' => $this->signature_verified,
            'status'             => $this->status->value,
            'status_label'       => $this->status->label(),
            'error_message'      => $this->error_message,
            'processed_at'       => $this->processed_at?->toISOString(),
            'created_at'         => $this->created_at->toISOString(),
        ];
    }
}
