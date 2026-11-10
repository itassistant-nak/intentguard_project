<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Http\Requests\OpenDisputeRequest;
use App\Http\Requests\ResolveDisputeRequest;
use App\Http\Requests\RespondDisputeRequest;
use App\Http\Resources\CustomerDisputeResource;
use App\Models\CustomerDispute;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerDisputeController
{
    public function __construct(
        private readonly ComplianceService $complianceService,
    ) {}

    /**
     * POST /api/v1/intents/{intentId}/disputes
     *
     * Customer formally opens a dispute against a purchase intent.
     */
    public function store(OpenDisputeRequest $request, int $intentId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $intent = PurchaseIntent::where('id', $intentId)
            ->where('customer_id', $user->id)
            ->first();

        if ($intent === null) {
            return response()->json([
                'code'    => 'INTENT_NOT_FOUND',
                'message' => 'No purchase intent found for the given ID, or access denied.',
            ], 404);
        }

        $validated = $request->validated();
        $category = DisputeCategory::from($validated['dispute_category']);

        $dispute = $this->complianceService->openDispute(
            intent:             $intent,
            customer:           $user,
            category:           $category,
            description:        $validated['description'],
            claimedAmountCents: $validated['claimed_amount_cents'] ?? null,
            evidenceSnapshotId: $validated['evidence_snapshot_id'] ?? null,
        );

        $dispute->load(['customer', 'merchant']);

        return response()->json(new CustomerDisputeResource($dispute), 201);
    }

    /**
     * GET /api/v1/disputes
     *
     * List disputes scoped to role (customer sees theirs, admin sees all).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = CustomerDispute::with(['customer', 'merchant', 'resolver'])->latest();

        if (!$user->isAdmin()) {
            $query->where('customer_id', $user->id);
        }

        $disputes = $query->paginate(15);

        return CustomerDisputeResource::collection($disputes);
    }

    /**
     * GET /api/v1/disputes/{id}
     *
     * View detailed dispute record with evidence and resolution state.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $dispute = CustomerDispute::with(['customer', 'merchant', 'evidenceSnapshot', 'resolver'])->find($id);

        if ($dispute === null) {
            return response()->json([
                'code'    => 'DISPUTE_NOT_FOUND',
                'message' => 'No dispute found for the given ID.',
            ], 404);
        }

        if (!$user->isAdmin() && $dispute->customer_id !== $user->id) {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'Access denied to this dispute.',
            ], 403);
        }

        return response()->json(new CustomerDisputeResource($dispute));
    }

    /**
     * POST /api/v1/disputes/{id}/respond
     *
     * Merchant submits an explanation or proposed remedy in response to a dispute.
     */
    public function respond(RespondDisputeRequest $request, int $id): JsonResponse
    {
        $dispute = CustomerDispute::find($id);

        if ($dispute === null) {
            return response()->json([
                'code'    => 'DISPUTE_NOT_FOUND',
                'message' => 'No dispute found for the given ID.',
            ], 404);
        }

        $updated = $this->complianceService->respondToDispute(
            dispute:          $dispute,
            merchantResponse: $request->validated('merchant_response'),
        );

        return response()->json(new CustomerDisputeResource($updated));
    }

    /**
     * POST /api/v1/disputes/{id}/resolve
     *
     * Administrator formally resolves a dispute (refund, replacement, or dismissal).
     */
    public function resolve(ResolveDisputeRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $dispute = CustomerDispute::find($id);

        if ($dispute === null) {
            return response()->json([
                'code'    => 'DISPUTE_NOT_FOUND',
                'message' => 'No dispute found for the given ID.',
            ], 404);
        }

        $status = DisputeStatus::from($request->validated('status'));

        try {
            $updated = $this->complianceService->resolveDispute(
                dispute:  $dispute,
                resolver: $user,
                status:   $status,
                notes:    $request->validated('notes'),
            );
        } catch (\DomainException $e) {
            return response()->json([
                'code'    => 'INVALID_DISPUTE_STATE',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(new CustomerDisputeResource($updated));
    }
}
