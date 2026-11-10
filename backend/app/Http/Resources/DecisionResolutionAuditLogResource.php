<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DecisionResolutionAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DecisionResolutionAuditLog */
class DecisionResolutionAuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'verification_decision_id' => $this->verification_decision_id,
            'action'                   => $this->action->value,
            'action_label'             => $this->action->label(),
            'actor_type'               => $this->actor_type->value,
            'actor_type_label'         => $this->actor_type->label(),
            'actor_id'                 => $this->actor_id,
            'actor_name'               => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'reason'                   => $this->reason,
            'metadata'                 => $this->metadata_json,
            'created_at'               => $this->created_at->toISOString(),
        ];
    }
}
