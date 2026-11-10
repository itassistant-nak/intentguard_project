<?php

declare(strict_types=1);

namespace App\Services\PayPal;

use App\Enums\PaymentAuthorizationStatus;
use App\Enums\WebhookEventStatus;
use App\Models\PaymentAuthorization;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayPalWebhookService
{
    public function __construct(
        private readonly PayPalClient $client,
    ) {}

    /**
     * Ingests, cryptographically verifies, and idempotently processes an incoming PayPal webhook event.
     */
    public function handleWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();
        $eventId = (string) ($payload['id'] ?? '');

        if ($eventId === '') {
            throw new \InvalidArgumentException("Incoming webhook payload does not contain an event ID.");
        }

        // Idempotency: prevent replay attack or duplicate processing
        $existing = WebhookEvent::byEventId($eventId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $eventType = (string) ($payload['event_type'] ?? 'UNKNOWN');
        $resourceType = (string) ($payload['resource_type'] ?? null);
        $resource = $payload['resource'] ?? [];
        $resourceId = (string) ($resource['id'] ?? null);

        // Verify cryptographic transmission signature
        $isSignatureValid = $this->client->verifyWebhookSignature($request);

        if (!$isSignatureValid) {
            return WebhookEvent::create([
                'gateway'            => 'paypal',
                'event_id'           => $eventId,
                'event_type'         => $eventType,
                'resource_type'      => $resourceType,
                'resource_id'        => $resourceId,
                'signature_verified' => false,
                'status'             => WebhookEventStatus::FAILED,
                'payload_json'       => $payload,
                'error_message'      => 'Cryptographic transmission signature validation failed.',
                'processed_at'       => Carbon::now(),
            ]);
        }

        return DB::transaction(function () use ($eventId, $eventType, $resourceType, $resourceId, $payload, $resource) {
            $webhookEvent = WebhookEvent::create([
                'gateway'            => 'paypal',
                'event_id'           => $eventId,
                'event_type'         => $eventType,
                'resource_type'      => $resourceType,
                'resource_id'        => $resourceId,
                'signature_verified' => true,
                'status'             => WebhookEventStatus::PROCESSED,
                'payload_json'       => $payload,
                'processed_at'       => Carbon::now(),
            ]);

            // Synchronize internal state based on event type
            $this->processEventPayload($eventType, $resource);

            return $webhookEvent;
        });
    }

    /**
     * Internal event payload processor.
     *
     * @param array<string, mixed> $resource
     */
    private function processEventPayload(string $eventType, array $resource): void
    {
        $now = Carbon::now();

        switch ($eventType) {
            case 'PAYMENT.AUTHORIZATION.CREATED':
                $authId = (string) ($resource['id'] ?? '');
                $orderId = (string) ($resource['supplementary_data']['related_ids']['order_id'] ?? '');

                if ($orderId !== '') {
                    PaymentAuthorization::where('order_id', $orderId)
                        ->whereNull('authorization_id')
                        ->update([
                            'authorization_id' => $authId,
                            'status'           => PaymentAuthorizationStatus::AUTHORIZED,
                            'authorized_at'    => $now,
                        ]);
                }
                break;

            case 'PAYMENT.CAPTURE.COMPLETED':
                $captureId = (string) ($resource['id'] ?? '');
                $authId = (string) ($resource['supplementary_data']['related_ids']['authorization_id'] ?? '');

                if ($authId !== '') {
                    PaymentAuthorization::where('authorization_id', $authId)
                        ->update([
                            'capture_id'  => $captureId,
                            'status'      => PaymentAuthorizationStatus::CAPTURED,
                            'captured_at' => $now,
                        ]);
                }
                break;

            case 'PAYMENT.AUTHORIZATION.VOIDED':
                $authId = (string) ($resource['id'] ?? '');

                if ($authId !== '') {
                    PaymentAuthorization::where('authorization_id', $authId)
                        ->update([
                            'status'    => PaymentAuthorizationStatus::VOIDED,
                            'voided_at' => $now,
                        ]);
                }
                break;
        }
    }
}
