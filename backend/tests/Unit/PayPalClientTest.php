<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\PayPal\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalClientTest extends TestCase
{
    use RefreshDatabase;

    private PayPalClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->client = new PayPalClient([
            'base_url'      => 'https://api-m.sandbox.paypal.com',
            'client_id'     => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'webhook_id'    => 'test_webhook_id',
        ]);
    }

    public function test_get_access_token_retrieves_and_caches_token(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token_12345',
                'token_type'   => 'Bearer',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $token = $this->client->getAccessToken();
        $this->assertSame('mock_token_12345', $token);

        // Subsequent call should use cache without hitting HTTP
        $cachedToken = $this->client->getAccessToken();
        $this->assertSame('mock_token_12345', $cachedToken);

        Http::assertSentCount(1);
    }

    public function test_create_order_sends_authorize_intent(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_999888',
                'status' => 'CREATED',
                'intent' => 'AUTHORIZE',
            ], 201),
        ]);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'customer_id'      => $user->id,
            'unit_price_cents' => 4999,
            'quantity'         => 2,
            'currency'         => 'USD',
        ]);

        $order = $this->client->createOrder($intent);

        $this->assertSame('ORDER_999888', $order['id']);
        $this->assertSame('AUTHORIZE', $order['intent']);

        Http::assertSent(function ($request) {
            if ($request->url() === 'https://api-m.sandbox.paypal.com/v2/checkout/orders') {
                $body = $request->data();
                return $body['intent'] === 'AUTHORIZE'
                    && $body['purchase_units'][0]['amount']['value'] === '99.98'
                    && $body['purchase_units'][0]['amount']['currency_code'] === 'USD';
            }
            return true;
        });
    }

    public function test_authorize_order_calls_paypal_authorize_endpoint(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_123/authorize' => Http::response([
                'id'             => 'ORDER_123',
                'status'         => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id'     => 'AUTH_456',
                                    'status' => 'CREATED',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $res = $this->client->authorizeOrder('ORDER_123');

        $this->assertSame('ORDER_123', $res['id']);
        $this->assertSame('AUTH_456', $res['purchase_units'][0]['payments']['authorizations'][0]['id']);
    }

    public function test_capture_authorization_calls_capture_endpoint(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_456/capture' => Http::response([
                'id'     => 'CAPTURE_789',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $res = $this->client->captureAuthorization('AUTH_456', 9999, 'USD');

        $this->assertSame('CAPTURE_789', $res['id']);
        $this->assertSame('COMPLETED', $res['status']);
    }

    public function test_void_authorization_calls_void_endpoint(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_456/void' => Http::response(null, 204),
        ]);

        $res = $this->client->voidAuthorization('AUTH_456');

        $this->assertSame('VOIDED', $res['status']);
    }

    public function test_verify_webhook_signature_validates_headers(): void
    {
        // Missing headers return false without making HTTP call
        $invalidRequest = Request::create('/webhooks/paypal', 'POST', ['id' => 'WH-123']);
        $this->assertFalse($this->client->verifyWebhookSignature($invalidRequest));

        // Complete headers verify against PayPal verification endpoint
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ], 200),
        ]);

        $validRequest = Request::create(
            '/webhooks/paypal',
            'POST',
            ['id' => 'WH-123'],
            server: [
                'HTTP_PAYPAL_AUTH_ALGO'         => 'SHA256withRSA',
                'HTTP_PAYPAL_CERT_URL'          => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'HTTP_PAYPAL_TRANSMISSION_ID'   => 'trans-uuid-123',
                'HTTP_PAYPAL_TRANSMISSION_SIG'  => 'signature-base64',
                'HTTP_PAYPAL_TRANSMISSION_TIME' => '2026-11-10T00:00:00Z',
            ]
        );

        $this->assertTrue($this->client->verifyWebhookSignature($validRequest));
    }
}
