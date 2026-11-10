<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\EvidenceType;
use App\Http\Requests\RecordEvidenceRequest;
use App\Http\Resources\PurchaseEvidenceResource;
use App\Models\PurchaseIntent;
use App\Services\EvidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseEvidenceController
{
    public function __construct(
        private readonly EvidenceService $evidenceService,
    ) {}

    /**
     * POST /api/v1/intents/{intentId}/evidence
     *
     * Record a new evidence record for an existing purchase intent.
     * The authenticated user must own the intent.
     * Returns 201 with the created evidence resource.
     */
    public function store(RecordEvidenceRequest $request, int $intentId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user   = $request->user();
        $intent = $this->resolveIntent($intentId, $user);

        if ($intent === null) {
            return $this->intentNotFound();
        }

        $validated = $request->validated();
        $type      = EvidenceType::from($validated['evidence_type']);

        // Route to the appropriate recording method
        if (!empty($validated['blob_path'])) {
            $evidence = $this->evidenceService->recordBlobReference(
                intent:      $intent,
                type:        $type,
                blobPath:    $validated['blob_path'],
                contentHash: $validated['content_hash'],
                options: [
                    'source_url'   => $validated['source_url'] ?? null,
                    'metadata'     => $validated['metadata'] ?? null,
                    'submitted_by' => $user->id,
                ],
            );
        } else {
            $evidence = $this->evidenceService->record(
                intent:     $intent,
                type:       $type,
                rawContent: $validated['content'],
                options: [
                    'source_url'   => $validated['source_url'] ?? null,
                    'metadata'     => $validated['metadata'] ?? null,
                    'submitted_by' => $user->id,
                ],
            );
        }

        $evidence->load('submitter');

        return response()->json(
            new PurchaseEvidenceResource($evidence),
            201,
        );
    }

    /**
     * GET /api/v1/intents/{intentId}/evidence
     *
     * List all evidence records for a purchase intent.
     * Optionally filter by ?type=product_page|checkout_screen|payment_quote|receipt
     */
    public function index(Request $request, int $intentId): JsonResponse|AnonymousResourceCollection
    {
        $user   = $request->user();
        $intent = $this->resolveIntent($intentId, $user);

        if ($intent === null) {
            return $this->intentNotFound();
        }

        $typeFilter = null;
        if ($request->filled('type')) {
            $typeFilter = EvidenceType::tryFrom($request->string('type')->toString());
            if ($typeFilter === null) {
                return response()->json([
                    'code'    => 'INVALID_EVIDENCE_TYPE',
                    'message' => 'Invalid evidence type filter. Valid values: ' . implode(', ', EvidenceType::values()),
                ], 422);
            }
        }

        $records = $this->evidenceService->forIntent($intent, $typeFilter);

        return PurchaseEvidenceResource::collection($records);
    }

    /**
     * GET /api/v1/evidence/{id}
     *
     * Retrieve a single evidence record by its ID.
     * Returns the full record including inline content_snapshot if stored.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $evidence = $this->evidenceService->find($id);

        if ($evidence === null) {
            return response()->json([
                'code'    => 'EVIDENCE_NOT_FOUND',
                'message' => 'No evidence record found for the given ID.',
            ], 404);
        }

        // Expose content_snapshot in the single-record response for audit retrieval
        $data = (new PurchaseEvidenceResource($evidence))->toArray($request);
        $data['content_snapshot'] = $evidence->content_snapshot;

        return response()->json($data);
    }

    /**
     * GET /api/v1/intents/{intentId}/evidence/provenance-chain
     *
     * Returns the full provenance chain for an intent — all evidence grouped by type,
     * with per-record and overall integrity check results.
     */
    public function provenanceChain(Request $request, int $intentId): JsonResponse
    {
        $user   = $request->user();
        $intent = $this->resolveIntent($intentId, $user);

        if ($intent === null) {
            return $this->intentNotFound();
        }

        $chain = $this->evidenceService->buildProvenanceChain($intent);

        return response()->json($chain);
    }

    /**
     * GET /api/v1/evidence/{id}/verify-integrity
     *
     * Re-verify the SHA-256 integrity of a specific evidence record.
     */
    public function verifyIntegrity(Request $request, int $id): JsonResponse
    {
        $evidence = $this->evidenceService->find($id);

        if ($evidence === null) {
            return response()->json([
                'code'    => 'EVIDENCE_NOT_FOUND',
                'message' => 'No evidence record found.',
            ], 404);
        }

        $result = $this->evidenceService->verifyIntegrity($evidence);

        return response()->json([
            'evidence_id'  => $evidence->id,
            'content_hash' => $evidence->content_hash,
            'integrity_ok' => $result,
            'message'      => match ($result) {
                true  => 'Evidence payload integrity verified — no tampering detected.',
                false => 'INTEGRITY VIOLATION — stored content does not match recorded hash.',
                null  => 'No inline snapshot stored. Integrity check not applicable for blob-only records.',
            },
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a PurchaseIntent by ID, scoped to the authenticated customer.
     * Admins may access any intent.
     */
    private function resolveIntent(int $intentId, \App\Models\User $user): ?PurchaseIntent
    {
        if ($user->isAdmin()) {
            return PurchaseIntent::find($intentId);
        }

        return PurchaseIntent::where('id', $intentId)
            ->where('customer_id', $user->id)
            ->first();
    }

    private function intentNotFound(): JsonResponse
    {
        return response()->json([
            'code'    => 'INTENT_NOT_FOUND',
            'message' => 'No purchase intent found for the given ID, or access denied.',
        ], 404);
    }
}
