<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResolutionActorType;
use App\Enums\ResolutionAuditAction;
use App\Models\DecisionResolutionAuditLog;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionResolutionAuditLog>
 */
class DecisionResolutionAuditLogFactory extends Factory
{
    protected $model = DecisionResolutionAuditLog::class;

    public function definition(): array
    {
        return [
            'verification_decision_id' => VerificationDecision::factory(),
            'action'                   => ResolutionAuditAction::HOLD_PLACED,
            'actor_type'               => ResolutionActorType::SYSTEM,
            'actor_id'                 => null,
            'reason'                   => 'Payment capture hold placed awaiting customer confirmation.',
            'metadata_json'            => ['source' => 'system_decision_engine'],
            'created_at'               => now(),
        ];
    }

    public function customerConfirmed(User $customer): static
    {
        return $this->state([
            'action'     => ResolutionAuditAction::CUSTOMER_CONFIRMED,
            'actor_type' => ResolutionActorType::CUSTOMER,
            'actor_id'   => $customer->id,
            'reason'     => 'Customer confirmed variation.',
        ]);
    }

    public function customerRejected(User $customer): static
    {
        return $this->state([
            'action'     => ResolutionAuditAction::CUSTOMER_REJECTED,
            'actor_type' => ResolutionActorType::CUSTOMER,
            'actor_id'   => $customer->id,
            'reason'     => 'Customer rejected deviation.',
        ]);
    }
}
