<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PaymentAuthorization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentAuthorization */
class PaymentAuthorizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'purchase_intent_id'       => $this->purchase_intent_id,
            'verification_decision_id' => $this->verification_decision_id,
            'gateway'                  => $this->gateway,
            'order_id'                 => $this->order_id,
            'authorization_id'         => $this->authorization_id,
            'capture_id'               => $this->capture_id,
            'amount_cents'             => $this->amount_cents,
            'amount'                   => $this->formattedAmount(),
            'currency'                 => $this->currency,
            'status'                   => $this->status->value,
            'status_label'             => $this->status->label(),
            'is_captured'              => $this->isCaptured(),
            'is_held'                  => $this->isHeld(),
            'is_voided'                => $this->isVoided(),
            'customer_payer_id'        => $this->customer_payer_id,
            'idempotency_key'          => $this->idempotency_key,
            'authorized_at'            => $this->authorized_at?->toISOString(),
            'captured_at'              => $this->captured_at?->toISOString(),
            'voided_at'                => $this->voided_at?->toISOString(),
            'verification_decision'    => new VerificationDecisionResource($this->whenLoaded('verificationDecision')),
            'created_at'               => $this->created_at->toISOString(),
            'updated_at'               => $this->updated_at->toISOString(),
        ];
    }
}
