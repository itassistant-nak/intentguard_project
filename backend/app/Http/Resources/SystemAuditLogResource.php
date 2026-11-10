<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SystemAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SystemAuditLog */
class SystemAuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'auditable_type'  => $this->auditable_type,
            'auditable_id'    => $this->auditable_id,
            'event'           => $this->event->value,
            'event_label'     => $this->event->label(),
            'actor_type'      => $this->actor_type,
            'actor_id'        => $this->actor_id,
            'actor_email'     => $this->actor_email,
            'actor_name'      => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'ip_address'      => $this->ip_address,
            'user_agent'      => $this->user_agent,
            'old_values'      => $this->old_values_json,
            'new_values'      => $this->new_values_json,
            'metadata'        => $this->metadata_json,
            'previous_hash'   => $this->previous_hash,
            'tamper_hash'     => $this->tamper_hash,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
