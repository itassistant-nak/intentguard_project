<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\MerchantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMerchantRequest;
use App\Http\Requests\UpdateMerchantStatusRequest;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\MerchantStatusAuditLogResource;
use App\Services\MerchantApprovalService;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService,
        protected MerchantApprovalService $approvalService,
    ) {}

    /**
     * Register a new merchant entity.
     * POST /api/v1/merchants/register
     */
    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $merchant = $this->merchantService->register($request->validated());

        return response()->json([
            'success' => true,
            'data' => new MerchantResource($merchant),
            'message' => 'Merchant registration submitted successfully. Please review and accept the Merchant Agreement.',
        ], 201);
    }

    /**
     * Show merchant profile by public IntentGuard ID or internal ID.
     * GET /api/v1/merchants/{id}
     */
    public function show(string $id): JsonResponse
    {
        $merchant = $this->merchantService->findByIdentifier($id);

        if ($merchant === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Merchant not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new MerchantResource($merchant),
        ]);
    }

    /**
     * List all merchants with optional status filtering.
     * GET /api/v1/merchants
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');
        $merchants = $this->merchantService->listMerchants(
            status: is_string($status) ? $status : null,
            perPage: (int) $request->query('per_page', 15),
        );

        return MerchantResource::collection($merchants);
    }

    /**
     * Update merchant status (verification, rejection, suspension, etc.).
     * POST /api/v1/merchants/{id}/status
     */
    public function updateStatus(UpdateMerchantStatusRequest $request, string $id): JsonResponse
    {
        $merchant = $this->merchantService->findByIdentifier($id);

        if ($merchant === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Merchant not found.',
                ],
            ], 404);
        }

        $validated = $request->validated();
        $targetStatus = MerchantStatus::from($validated['status']);

        $updated = $this->approvalService->updateStatus(
            merchant: $merchant,
            newStatus: $targetStatus,
            reason: $validated['reason'],
            actionedBy: $request->user(),
        );

        return response()->json([
            'success' => true,
            'data' => new MerchantResource($updated),
            'message' => 'Merchant status updated successfully.',
        ]);
    }

    /**
     * Retrieve status change audit logs for a merchant.
     * GET /api/v1/merchants/{id}/audit-log
     */
    public function auditLogs(string $id): JsonResponse
    {
        $merchant = $this->merchantService->findByIdentifier($id);

        if ($merchant === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Merchant not found.',
                ],
            ], 404);
        }

        $logs = $merchant->statusAuditLogs()->with('actionedByUser')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => MerchantStatusAuditLogResource::collection($logs),
        ]);
    }
}
