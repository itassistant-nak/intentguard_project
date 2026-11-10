<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SystemAuditLogResource;
use App\Models\SystemAuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Reporting\AdminReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminAuditController
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AdminReportingService $reportingService,
    ) {}

    /**
     * GET /api/v1/admin/audit-logs
     *
     * Retrieves filterable, paginated system-wide audit logs. Restricted to administrators.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'Only administrators can access system audit logs.',
            ], 403);
        }

        $query = SystemAuditLog::with('actor')->latest('id');

        if ($request->filled('event')) {
            $query->where('event', $request->query('event'));
        }

        if ($request->filled('actor_type')) {
            $query->where('actor_type', $request->query('actor_type'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->query('auditable_type'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', (int) $request->query('auditable_id'));
        }

        $logs = $query->paginate(25);

        return SystemAuditLogResource::collection($logs);
    }

    /**
     * GET /api/v1/admin/audit-logs/verify-chain
     *
     * Traverses the cryptographic hash chain and verifies mathematical provenance of all records.
     */
    public function verifyChain(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'Only administrators can verify audit chain provenance.',
            ], 403);
        }

        $limit = (int) $request->query('limit', 1000);
        $verificationResult = $this->auditLogService->verifyChain($limit);

        return response()->json([
            'success' => $verificationResult['is_intact'],
            'data'    => $verificationResult,
        ]);
    }

    /**
     * GET /api/v1/admin/metrics
     *
     * Aggregates platform-wide fraud prevention, transaction volume, and merchant health metrics.
     */
    public function metrics(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'code'    => 'FORBIDDEN',
                'message' => 'Only administrators can access platform analytics metrics.',
            ], 403);
        }

        $metrics = $this->reportingService->getPlatformMetrics();

        return response()->json([
            'success' => true,
            'data'    => $metrics,
        ]);
    }
}
