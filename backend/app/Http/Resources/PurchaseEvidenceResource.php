<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PurchaseEvidence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseEvidence */
class PurchaseEvidenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'purchase_intent_id'          => $this->purchase_intent_id,

            // Evidence type
            'evidence_type'               => $this->evidence_type->value,
            'evidence_type_label'         => $this->evidence_type->label(),
            'evidence_type_description'   => $this->evidence_type->description(),

            // Source
            'source_url'                  => $this->source_url,

            // Snapshot availability (do NOT expose raw content_snapshot in list responses)
            'has_inline_snapshot'         => $this->hasInlineSnapshot(),
            'has_blob_snapshot'           => $this->hasBlobSnapshot(),
            'content_snapshot_blob_path'  => $this->content_snapshot_blob_path,

            // Cryptographic integrity field
            'content_hash'                => $this->content_hash,

            // Metadata
            'metadata'                    => $this->metadata_json,

            // Submitter info (loaded only when relation is eager-loaded)
            'submitted_by'                => $this->whenLoaded('submitter', fn () => [
                'id'   => $this->submitter->id,
                'name' => $this->submitter->name,
            ]),

            // Timestamps
            'captured_at' => $this->captured_at->toISOString(),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
