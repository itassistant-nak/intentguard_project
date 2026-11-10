<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptAgreementRequest;
use App\Http\Resources\MerchantAgreementAcceptanceResource;
use App\Http\Resources\MerchantAgreementResource;
use App\Services\MerchantAgreementService;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantAgreementController extends Controller
{
    public function __construct(
        protected MerchantAgreementService $agreementService,
        protected MerchantService $merchantService,
    ) {}

    /**
     * Get the currently active legally binding merchant agreement.
     * GET /api/v1/agreements/active
     */
    public function active(): JsonResponse
    {
        $agreement = $this->agreementService->getActiveAgreement();

        if ($agreement === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No active agreement published.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new MerchantAgreementResource($agreement),
        ]);
    }

    /**
     * Submit digital acceptance of the merchant agreement.
     * POST /api/v1/merchants/{id}/agreements/accept
     */
    public function accept(AcceptAgreementRequest $request, string $id): JsonResponse
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

        $payload = $request->validated();
        $payload['ip_address'] = $request->ip() ?? '127.0.0.1';
        $payload['user_agent'] = $request->userAgent() ?? 'IntentGuard-Client/1.0';

        $acceptance = $this->agreementService->acceptAgreement($merchant, $payload);

        return response()->json([
            'success' => true,
            'data' => new MerchantAgreementAcceptanceResource($acceptance),
            'message' => 'Merchant Agreement accepted successfully.',
        ], 201);
    }

    /**
     * Get all agreements accepted by a merchant.
     * GET /api/v1/merchants/{id}/agreements
     */
    public function merchantAgreements(string $id): JsonResponse
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

        $acceptances = $merchant->agreementAcceptances()->with('agreement')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => MerchantAgreementAcceptanceResource::collection($acceptances),
        ]);
    }
}
