<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EvidenceType;
use App\Models\PurchaseEvidence;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class EvidenceService
{
    /**
     * Record a new piece of evidence for a purchase intent.
     *
     * The caller provides the raw content string (HTML, JSON, plain text) —
     * we compute and store its SHA-256 hash to establish tamper-evidence.
     * The raw content is stored inline in `content_snapshot` for audit retrieval.
     *
     * @param  PurchaseIntent  $intent
     * @param  EvidenceType    $type
     * @param  string          $rawContent  The full raw content to hash and store.
     * @param  array<string, mixed> $options Additional optional fields.
     * @return PurchaseEvidence
     */
    public function record(
        PurchaseIntent  $intent,
        EvidenceType    $type,
        string          $rawContent,
        array           $options = [],
    ): PurchaseEvidence {
        $contentHash = $this->computeHash($rawContent);

        return PurchaseEvidence::create([
            'purchase_intent_id'          => $intent->id,
            'evidence_type'               => $type,
            'source_url'                  => $options['source_url'] ?? null,
            'content_snapshot'            => $rawContent,
            'content_snapshot_blob_path'  => $options['blob_path'] ?? null,
            'content_hash'                => $contentHash,
            'metadata_json'               => $options['metadata'] ?? null,
            'submitted_by'                => $options['submitted_by'] ?? null,
            'captured_at'                 => Carbon::now(),
        ]);
    }

    /**
     * Record evidence with an external blob reference only (no inline snapshot).
     * Used when the content is too large to store in the database (e.g. full-page screenshot).
     *
     * The caller is responsible for storing the content to blob storage first
     * and providing the pre-computed hash for verification.
     *
     * @param  PurchaseIntent  $intent
     * @param  EvidenceType    $type
     * @param  string          $blobPath     Path/key in blob storage.
     * @param  string          $contentHash  SHA-256 of the blob content.
     * @param  array<string, mixed> $options
     * @return PurchaseEvidence
     */
    public function recordBlobReference(
        PurchaseIntent  $intent,
        EvidenceType    $type,
        string          $blobPath,
        string          $contentHash,
        array           $options = [],
    ): PurchaseEvidence {
        return PurchaseEvidence::create([
            'purchase_intent_id'          => $intent->id,
            'evidence_type'               => $type,
            'source_url'                  => $options['source_url'] ?? null,
            'content_snapshot'            => null,
            'content_snapshot_blob_path'  => $blobPath,
            'content_hash'                => $contentHash,
            'metadata_json'               => $options['metadata'] ?? null,
            'submitted_by'                => $options['submitted_by'] ?? null,
            'captured_at'                 => Carbon::now(),
        ]);
    }

    /**
     * Retrieve all evidence records for a given purchase intent,
     * optionally filtered by evidence type.
     *
     * @param  PurchaseIntent   $intent
     * @param  EvidenceType|null $type   If provided, filter to this type only.
     * @return Collection<int, PurchaseEvidence>
     */
    public function forIntent(
        PurchaseIntent $intent,
        ?EvidenceType  $type = null,
    ): Collection {
        $query = PurchaseEvidence::where('purchase_intent_id', $intent->id)
            ->with('submitter')
            ->orderBy('captured_at');

        if ($type !== null) {
            $query->where('evidence_type', $type->value);
        }

        return $query->get();
    }

    /**
     * Find a single evidence record by its ID.
     *
     * @param  int $id
     * @return PurchaseEvidence|null
     */
    public function find(int $id): ?PurchaseEvidence
    {
        return PurchaseEvidence::with(['purchaseIntent', 'submitter'])->find($id);
    }

    /**
     * Re-verify the integrity of a stored evidence record.
     * Recomputes the SHA-256 of the inline content_snapshot and compares with content_hash.
     *
     * Returns null if no inline snapshot is stored (blob-only records cannot be verified here).
     *
     * @param  PurchaseEvidence $evidence
     * @return bool|null  true = intact, false = tampered, null = no inline snapshot
     */
    public function verifyIntegrity(PurchaseEvidence $evidence): ?bool
    {
        if (!$evidence->hasInlineSnapshot()) {
            return null;
        }

        return $evidence->verifyContentIntegrity();
    }

    /**
     * Build a complete provenance chain for an intent.
     * Returns all evidence grouped by type, plus an overall integrity summary.
     *
     * @param  PurchaseIntent $intent
     * @return array{
     *   intent_id: int,
     *   total_records: int,
     *   chain: array<string, array<int, mixed>>,
     *   integrity_summary: array{total: int, intact: int, tampered: int, unverifiable: int}
     * }
     */
    public function buildProvenanceChain(PurchaseIntent $intent): array
    {
        $records = $this->forIntent($intent);

        $chain             = [];
        $totalIntact       = 0;
        $totalTampered     = 0;
        $totalUnverifiable = 0;

        foreach ($records as $evidence) {
            $typeKey = $evidence->evidence_type->value;
            $isIntact = $this->verifyIntegrity($evidence);

            if ($isIntact === true) {
                $totalIntact++;
            } elseif ($isIntact === false) {
                $totalTampered++;
            } else {
                $totalUnverifiable++;
            }

            $chain[$typeKey][] = [
                'id'           => $evidence->id,
                'content_hash' => $evidence->content_hash,
                'source_url'   => $evidence->source_url,
                'captured_at'  => $evidence->captured_at->toISOString(),
                'integrity_ok' => $isIntact,
                'has_inline'   => $evidence->hasInlineSnapshot(),
                'has_blob'     => $evidence->hasBlobSnapshot(),
            ];
        }

        return [
            'intent_id'     => $intent->id,
            'total_records' => $records->count(),
            'chain'         => $chain,
            'integrity_summary' => [
                'total'        => $records->count(),
                'intact'       => $totalIntact,
                'tampered'     => $totalTampered,
                'unverifiable' => $totalUnverifiable,
            ],
        ];
    }

    /**
     * Compute SHA-256 hash of a raw content string.
     */
    public function computeHash(string $rawContent): string
    {
        return hash('sha256', $rawContent);
    }
}
