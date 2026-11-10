<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentAuthorizationStatus;
use App\Models\Merchant;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_customer_can_create_paypal_order_for_their_intent(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_PAYPAL_123',
                'status' => 'CREATED',
                'intent' => 'AUTHORIZE',
            ], 201),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $merchant = Merchant::factory()->create();
        $intent = PurchaseIntent::factory()->create([
            'customer_id'      => $customer->id,
            'merchant_id'      => $merchant->id,
            'unit_price_cents' => 9999,
            'quantity'         => 1,
            'currency'         => 'USD',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/intents/{$intent->id}/paypal/order", [
            'idempotency_key' => 'idemp-order-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_id', 'ORDER_PAYPAL_123')
            ->assertJsonPath('data.status', 'created')
            ->assertJsonPath('data.amount_cents', 9999);

        $this->assertDatabaseHas('payment_authorizations', [
            'purchase_intent_id' => $intent->id,
            'order_id'           => 'ORDER_PAYPAL_123',
            'idempotency_key'    => 'idemp-order-123',
        ]);
    }

    public function test_customer_cannot_create_order_for_another_customers_intent(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create(['customer_id' => $customer1->id]);

        Sanctum::actingAs($customer2);

        $response = $this->postJson("/api/v1/intents/{$intent->id}/paypal/order", [
            'idempotency_key' => 'idemp-test',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INTENT_NOT_FOUND');
    }

    public function test_customer_can_authorize_order_and_triggers_interception(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORD_AUTH_999/authorize' => Http::response([
                'id'             => 'ORD_AUTH_999',
                'status'         => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id'     => 'AUTH_CAPTURE_NOW',
                                    'status' => 'CREATED',
                                ],
                            ],
                        ],
                    ],
                ],
                'payer' => ['payer_id' => 'PAYER_999'],
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_CAPTURE_NOW/capture' => Http::response([
                'id'     => 'CAP_EXECUTED_999',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $merchant = Merchant::factory()->create(['compliance_score' => 100.00]);
        $intent = PurchaseIntent::factory()->create([
            'customer_id'      => $customer->id,
            'merchant_id'      => $merchant->id,
            'unit_price_cents' => 9999,
            'quantity'         => 1,
            'currency'         => 'USD',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/intents/{$intent->id}/paypal/authorize", [
            'order_id'         => 'ORD_AUTH_999',
            'checkout_payload' => [
                'merchant_id'       => $merchant->id,
                'currency'          => 'USD',
                'unit_price_cents'  => 9999,
                'quantity'          => 1,
                'total_price_cents' => 9999,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'captured')
            ->assertJsonPath('data.authorization_id', 'AUTH_CAPTURE_NOW')
            ->assertJsonPath('data.capture_id', 'CAP_EXECUTED_999');
    }

    public function test_customer_can_view_payment_authorization(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create(['customer_id' => $customer->id]);
        $paymentAuth = PaymentAuthorization::factory()->create(['purchase_intent_id' => $intent->id]);

        Sanctum::actingAs($customer);

        $response = $this->getJson("/api/v1/payments/{$paymentAuth->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $paymentAuth->id)
            ->assertJsonPath('data.order_id', $paymentAuth->order_id);
    }

    public function test_customer_can_release_held_capture(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_HELD_123/capture' => Http::response([
                'id'     => 'CAP_RELEASED_123',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create(['customer_id' => $customer->id]);
        $paymentAuth = PaymentAuthorization::factory()->held()->create([
            'purchase_intent_id' => $intent->id,
            'authorization_id'   => 'AUTH_HELD_123',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/payments/{$paymentAuth->id}/capture-held", [
            'reason' => 'I accept the slightly different shade of blue.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'captured')
            ->assertJsonPath('data.capture_id', 'CAP_RELEASED_123');

        $this->assertSame(PaymentAuthorizationStatus::CAPTURED, $paymentAuth->fresh()->status);
    }

    public function test_customer_can_void_held_capture(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_HELD_456/void' => Http::response(null, 204),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create(['customer_id' => $customer->id]);
        $paymentAuth = PaymentAuthorization::factory()->held()->create([
            'purchase_intent_id' => $intent->id,
            'authorization_id'   => 'AUTH_HELD_456',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/payments/{$paymentAuth->id}/void-held", [
            'reason' => 'Refurbished condition is unacceptable.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'voided');

        $this->assertSame(PaymentAuthorizationStatus::VOIDED, $paymentAuth->fresh()->status);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/intents/1/paypal/order', [
            'idempotency_key' => 'key-1',
        ]);

        $response->assertStatus(401);
    }
}
