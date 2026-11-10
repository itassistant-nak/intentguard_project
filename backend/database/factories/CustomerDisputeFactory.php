<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Models\CustomerDispute;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerDispute>
 */
class CustomerDisputeFactory extends Factory
{
    protected $model = CustomerDispute::class;

    public function definition(): array
    {
        return [
            'purchase_intent_id'   => PurchaseIntent::factory(),
            'customer_id'          => User::factory()->create(['role' => 'customer']),
            'merchant_id'          => Merchant::factory(),
            'dispute_category'     => DisputeCategory::ITEM_NOT_AS_DESCRIBED,
            'description'          => 'The item delivered was an older model variant than what was authorized.',
            'claimed_amount_cents' => 9999,
            'evidence_snapshot_id' => null,
            'status'               => DisputeStatus::OPENED,
        ];
    }

    public function underMerchantResponse(): static
    {
        return $this->state([
            'status'                => DisputeStatus::UNDER_MERCHANT_RESPONSE,
            'merchant_response'     => 'We have reviewed the warehouse logs and offered a complimentary replacement.',
            'merchant_responded_at' => now(),
        ]);
    }
}
