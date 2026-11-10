<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\VerificationDecision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VerificationDecision */
class VerificationDecisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discrepancies = $this->discrepancy_flags_json ?? [];

        return [
            'id'                   => $this->id,
            'purchase_intent_id'   => $this->purchase_intent_id,
            'confidence_score'     => round((float) $this->confidence_score, 4),
            'verdict'                 => $this->verdict->value,
            'verdict_label'           => $this->verdict->label(),
            'hold_payment_capture'    => $this->hold_payment_capture,
            'resolution_status'       => $this->resolution_status?->value ?? \App\Enums\DecisionResolutionStatus::UNRESOLVED->value,
            'resolution_status_label' => $this->resolution_status?->label() ?? \App\Enums\DecisionResolutionStatus::UNRESOLVED->label(),
            'is_pending_hold'         => $this->isPendingHold(),
            'resolved_at'             => $this->resolved_at?->toISOString(),
            'discrepancy_count'       => count($discrepancies),
            'discrepancies'           => $discrepancies,
            'checkout_payload'        => $this->checkout_payload_json,
            'resolution_audit_logs'   => DecisionResolutionAuditLogResource::collection($this->whenLoaded('resolutionAuditLogs')),
            'decided_at'              => $this->decided_at->toISOString(),
            'created_at'              => $this->created_at->toISOString(),
        ];
    }
}
