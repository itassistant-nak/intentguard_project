<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MerchantComplianceEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MerchantComplianceEvent */
class MerchantComplianceEventResource extends JsonResource
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
            'merchant_id'              => $this->merchant_id,
            'purchase_intent_id'       => $this->purchase_intent_id,
            'verification_decision_id' => $this->verification_decision_id,
            'event_type'               => $this->event_type->value,
            'event_type_label'         => $this->event_type->label(),
            'severity'                 => $this->severity,
            'score_delta'              => round((float) $this->score_delta, 2),
            'score_before'             => round((float) $this->score_before, 2),
            'score_after'              => round((float) $this->score_after, 2),
            'notes'                    => $this->notes,
            'evidence_reference'       => $this->evidence_reference,
            'appeal_status'            => $this->appeal_status->value,
            'appeal_status_label'      => $this->appeal_status->label(),
            'appeal_reason'            => $this->appeal_reason,
            'appeal_evidence_url'      => $this->appeal_evidence_url,
            'appealed_at'              => $this->appealed_at?->toISOString(),
            'adjudicated_at'           => $this->adjudicated_at?->toISOString(),
            'adjudicated_by'           => $this->adjudicated_by,
            'adjudicator_name'         => $this->whenLoaded('adjudicator', fn () => $this->adjudicator?->name),
            'adjudication_notes'       => $this->adjudication_notes,
            'created_at'               => $this->created_at->toISOString(),
        ];
    }
}
