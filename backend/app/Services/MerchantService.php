<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MerchantService
{
    /**
     * Generate collision-resistant IntentGuard merchant identifier.
     * Format: IG-MCH-XXXXXXXX (8 uppercase hex characters)
     */
    public function generateIntentGuardId(): string
    {
        do {
            $hex = strtoupper(bin2hex(random_bytes(4)));
            $id = "IG-MCH-{$hex}";
        } while (Merchant::where('intentguard_id', $id)->exists());

        return $id;
    }

    /**
     * Register a new merchant entity.
     *
     * @param array<string, mixed> $data
     */
    public function register(array $data): Merchant
    {
        $data['intentguard_id'] = $this->generateIntentGuardId();
        $data['status'] = MerchantStatus::PENDING_REVIEW;
        $data['compliance_score'] = 100.00;

        return Merchant::create($data);
    }

    /**
     * Find merchant by public IntentGuard ID or internal ID.
     */
    public function findByIdentifier(string|int $identifier): ?Merchant
    {
        return Merchant::byIdOrIntentGuardId($identifier)->first();
    }

    /**
     * List paginated merchants with optional status filter.
     */
    public function listMerchants(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Merchant::query()->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }
}
