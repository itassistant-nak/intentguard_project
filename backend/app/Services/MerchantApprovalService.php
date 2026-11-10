<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\MerchantAgreement;
use App\Models\MerchantStatusAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchantApprovalService
{
    public function __construct(
        protected MerchantAgreementService $agreementService,
    ) {}

    /**
     * Transition merchant onboarding status with mandatory audit trail.
     *
     * @throws ValidationException
     */
    public function updateStatus(
        Merchant $merchant,
        MerchantStatus $newStatus,
        string $reason,
        ?User $actionedBy = null,
    ): Merchant {
        // Enforce business rule: Merchant cannot be verified without accepting current active agreement
        if ($newStatus === MerchantStatus::VERIFIED) {
            $activeAgreement = $this->agreementService->getActiveAgreement();

            if ($activeAgreement === null || !$merchant->hasAcceptedAgreement($activeAgreement)) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Merchant cannot be verified without executing the active Merchant Agreement.',
                    ],
                ]);
            }
        }

        return DB::transaction(function () use ($merchant, $newStatus, $reason, $actionedBy) {
            $previousStatus = $merchant->status->value;

            // Record immutable audit log
            MerchantStatusAuditLog::create([
                'merchant_id' => $merchant->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus->value,
                'reason' => $reason,
                'actioned_by' => $actionedBy?->id,
            ]);

            // Update merchant state
            $merchant->status = $newStatus;
            $merchant->verification_notes = $reason;

            if ($newStatus === MerchantStatus::VERIFIED) {
                $merchant->verified_at = now();
                $merchant->verified_by = $actionedBy?->id;
            }

            $merchant->save();

            return $merchant;
        });
    }
}
