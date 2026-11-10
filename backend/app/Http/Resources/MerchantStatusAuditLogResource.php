<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MerchantStatusAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MerchantStatusAuditLog
 */
class MerchantStatusAuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'reason' => $this->reason,
            'actioned_by' => $this->actioned_by,
            'actioned_by_name' => $this->actionedByUser?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
