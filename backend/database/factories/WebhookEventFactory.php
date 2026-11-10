<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'gateway'            => 'paypal',
            'event_id'           => 'WH-' . fake()->regexify('[A-Z0-9]{17}'),
            'event_type'         => 'PAYMENT.AUTHORIZATION.CREATED',
            'resource_type'      => 'authorization',
            'resource_id'        => 'AUTH_' . fake()->regexify('[A-Z0-9]{17}'),
            'signature_verified' => true,
            'status'             => WebhookEventStatus::PROCESSED,
            'payload_json'       => [
                'id'         => 'WH-test',
                'event_type' => 'PAYMENT.AUTHORIZATION.CREATED',
            ],
            'error_message'      => null,
            'processed_at'       => now(),
        ];
    }
}
