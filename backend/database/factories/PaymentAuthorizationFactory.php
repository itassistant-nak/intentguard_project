<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentAuthorizationStatus;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAuthorization>
 */
class PaymentAuthorizationFactory extends Factory
{
    protected $model = PaymentAuthorization::class;

    public function definition(): array
    {
        return [
            'purchase_intent_id'       => PurchaseIntent::factory(),
            'verification_decision_id' => null,
            'gateway'                  => 'paypal',
            'order_id'                 => 'ORD_' . fake()->regexify('[A-Z0-9]{17}'),
            'authorization_id'         => 'AUTH_' . fake()->regexify('[A-Z0-9]{17}'),
            'capture_id'               => null,
            'amount_cents'             => 9999,
            'currency'                 => 'USD',
            'status'                   => PaymentAuthorizationStatus::AUTHORIZED,
            'idempotency_key'          => 'key_' . fake()->uuid(),
            'customer_payer_id'        => 'PAYER_' . fake()->regexify('[A-Z0-9]{13}'),
            'metadata_json'            => ['source' => 'sandbox_test'],
            'authorized_at'            => now(),
            'captured_at'              => null,
            'voided_at'                => null,
        ];
    }

    public function captured(): static
    {
        return $this->state([
            'status'      => PaymentAuthorizationStatus::CAPTURED,
            'capture_id'  => 'CAP_' . fake()->regexify('[A-Z0-9]{17}'),
            'captured_at' => now(),
        ]);
    }

    public function held(): static
    {
        return $this->state([
            'status'      => PaymentAuthorizationStatus::HELD,
            'capture_id'  => null,
            'captured_at' => null,
        ]);
    }

    public function voided(): static
    {
        return $this->state([
            'status'    => PaymentAuthorizationStatus::VOIDED,
            'voided_at' => now(),
        ]);
    }
}
