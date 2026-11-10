<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Engines\SemanticIntegrityEngine\SemanticIntegrityEngine;
use App\Enums\PurchaseIntentStatus;
use App\Enums\VerificationVerdict;
use App\Http\Requests\VerifyCheckoutRequest;
use App\Http\Resources\VerificationDecisionResource;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IntegrityVerificationController
{
    public function __construct(
        private readonly SemanticIntegrityEngine $integrityEngine,
    ) {}

    /**
     * POST /api/v1/intents/{intentId}/verify
     *
     * Evaluates a merchant checkout payload against a recorded Purchase Intent.
     * Executes both deterministic and semantic evaluation layers, assigns confidence
     * score, and returns an actionable verification decision.
     */
    public function verify(VerifyCheckoutRequest $request, int $intentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $intent = $this->resolveIntent($intentId, $user);

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given ID, or access denied.',
            ], 404);
        }

        $checkoutPayload = $request->validated();

        // Execute Semantic Purchase Integrity Engine
        $report = $this->integrityEngine->verify($intent, $checkoutPayload);

        // Persist Verification Decision in transaction
        $decision = DB::transaction(function () use ($intent, $checkoutPayload, $report) {
            $record = VerificationDecision::create([
                'purchase_intent_id'     => $intent->id,
                'checkout_payload_json'  => $checkoutPayload,
                'discrepancy_flags_json' => array_map(fn ($d) => $d->toArray(), $report->discrepancies),
                'confidence_score'       => $report->confidenceScore,
                'verdict'                => $report->verdict,
                'hold_payment_capture'   => $report->holdPaymentCapture,
                'decided_at'             => Carbon::now(),
            ]);

            // Synchronize PurchaseIntent status
            if ($report->verdict === VerificationVerdict::MATCH) {
                $intent->update(['status' => PurchaseIntentStatus::VERIFIED]);
            } elseif ($report->verdict === VerificationVerdict::MISMATCH) {
                $intent->update(['status' => PurchaseIntentStatus::MISMATCHED]);
            } elseif ($intent->status === PurchaseIntentStatus::PENDING) {
                $intent->update(['status' => PurchaseIntentStatus::CONFIRMED]);
            }

            return $record;
        });

        return response()->json(
            new VerificationDecisionResource($decision),
            201,
        );
    }

    /**
     * GET /api/v1/intents/{intentId}/verifications
     *
     * List all verification decision records performed on an intent.
     */
    public function index(Request $request, int $intentId): JsonResponse|AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $intent = $this->resolveIntent($intentId, $user);

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given ID, or access denied.',
            ], 404);
        }

        $decisions = $intent->verificationDecisions()->get();

        return VerificationDecisionResource::collection($decisions);
    }

    /**
     * GET /api/v1/verifications/{id}
     *
     * Retrieve a specific verification decision by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $decision = VerificationDecision::with('purchaseIntent')->find($id);

        if ($decision === null) {
            return response()->json([
                'code'    => 'VERIFICATION_NOT_FOUND',
                'message' => 'No verification decision found for the given ID.',
            ], 404);
        }

        // Authorization: customer must own the intent or user must be admin
        if (!$user->isAdmin() && $decision->purchaseIntent->customer_id !== $user->id) {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'Access denied to this verification record.',
            ], 403);
        }

        return response()->json(new VerificationDecisionResource($decision));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveIntent(int $intentId, User $user): ?PurchaseIntent
    {
        if ($user->isAdmin()) {
            return PurchaseIntent::find($intentId);
        }

        return PurchaseIntent::where('id', $intentId)
            ->where('customer_id', $user->id)
            ->first();
    }
}
