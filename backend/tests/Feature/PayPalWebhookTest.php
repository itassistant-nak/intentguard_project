<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentAuthorizationStatus;
use App\Models\PaymentAuthorization;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_webhook_with_valid_signature_is_processed(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ]);

        $payload = [
            'id'            => 'WH-EVENT-101',
            'event_type'    => 'PAYMENT.AUTHORIZATION.CREATED',
            'resource_type' => 'authorization',
            'resource'      => [
                'id' => 'AUTH-WEBHOOK-1',
            ],
        ];

        $headers = [
            'PAYPAL-AUTH-ALGO'         => 'SHA256withRSA',
            'PAYPAL-CERT-URL'          => 'https://api.sandbox.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID'   => 'trans-101',
            'PAYPAL-TRANSMISSION-SIG'  => 'valid-sig',
            'PAYPAL-TRANSMISSION-TIME' => '2026-11-10T00:00:00Z',
        ];

        $response = $this->postJson('/api/v1/webhooks/paypal', $payload, $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.event_id', 'WH-EVENT-101')
            ->assertJsonPath('data.signature_verified', true)
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('webhook_events', [
            'event_id'           => 'WH-EVENT-101',
            'signature_verified' => true,
        ]);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'FAILURE',
            ], 200),
        ]);

        $payload = [
            'id'         => 'WH-EVENT-FORGED',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ];

        $headers = [
            'PAYPAL-AUTH-ALGO'         => 'SHA256withRSA',
            'PAYPAL-CERT-URL'          => 'https://api.sandbox.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID'   => 'trans-forged',
            'PAYPAL-TRANSMISSION-SIG'  => 'forged-signature',
            'PAYPAL-TRANSMISSION-TIME' => '2026-11-10T00:00:00Z',
        ];

        $response = $this->postJson('/api/v1/webhooks/paypal', $payload, $headers);

        $response->assertStatus(400)
            ->assertJsonPath('code', 'WEBHOOK_SIGNATURE_INVALID');

        $this->assertDatabaseHas('webhook_events', [
            'event_id'           => 'WH-EVENT-FORGED',
            'signature_verified' => false,
            'status'             => 'failed',
        ]);
    }

    public function test_duplicate_webhook_is_deduplicated(): void
    {
        WebhookEvent::factory()->create([
            'event_id'           => 'WH-DUP-999',
            'signature_verified' => true,
            'status'             => 'processed',
        ]);

        $payload = [
            'id'         => 'WH-DUP-999',
            'event_type' => 'PAYMENT.AUTHORIZATION.CREATED',
        ];

        $response = $this->postJson('/api/v1/webhooks/paypal', $payload);

        // Deduplication returns existing processed event without re-verifying
        $response->assertStatus(200)
            ->assertJsonPath('data.event_id', 'WH-DUP-999');

        $this->assertSame(1, WebhookEvent::where('event_id', 'WH-DUP-999')->count());
    }

    public function test_capture_completed_webhook_synchronizes_payment_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ]);

        $auth = PaymentAuthorization::factory()->create([
            'authorization_id' => 'AUTH-SYNC-123',
            'status'           => PaymentAuthorizationStatus::AUTHORIZED,
        ]);

        $payload = [
            'id'            => 'WH-CAPTURE-SYNC',
            'event_type'    => 'PAYMENT.CAPTURE.COMPLETED',
            'resource_type' => 'capture',
            'resource'      => [
                'id'                 => 'CAP-FROM-WEBHOOK',
                'supplementary_data' => [
                    'related_ids' => [
                        'authorization_id' => 'AUTH-SYNC-123',
                    ],
                ],
            ],
        ];

        $headers = [
            'PAYPAL-AUTH-ALGO'         => 'SHA256withRSA',
            'PAYPAL-CERT-URL'          => 'https://api.sandbox.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID'   => 'trans-cap',
            'PAYPAL-TRANSMISSION-SIG'  => 'valid-sig',
            'PAYPAL-TRANSMISSION-TIME' => '2026-11-10T00:00:00Z',
        ];

        $this->postJson('/api/v1/webhooks/paypal', $payload, $headers)->assertStatus(200);

        $freshAuth = $auth->fresh();
        $this->assertSame(PaymentAuthorizationStatus::CAPTURED, $freshAuth->status);
        $this->assertSame('CAP-FROM-WEBHOOK', $freshAuth->capture_id);
    }
}
