<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Engines\SemanticIntegrityEngine\SemanticIntegrityEngine;
use App\Engines\TransactionDecisionEngine\TransactionDecisionEngine;
use App\Enums\PaymentAuthorizationStatus;
use App\Enums\PurchaseIntentStatus;
use App\Models\Merchant;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\PayPal\PayPalClient;
use App\Services\PayPal\PayPalGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayPalGatewayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $client = new PayPalClient([
            'base_url'      => 'https://api-m.sandbox.paypal.com',
            'client_id'     => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'webhook_id'    => 'test_webhook_id',
        ]);

        $integrityEngine = new SemanticIntegrityEngine();
        $decisionEngine = new TransactionDecisionEngine();

        $this->service = new PayPalGatewayService($client, $integrityEngine, $decisionEngine);
    }

    public function test_create_order_for_intent_creates_payment_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_111',
                'status' => 'CREATED',
                'intent' => 'AUTHORIZE',
            ], 201),
        ]);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'customer_id'      => $user->id,
            'unit_price_cents' => 5000,
            'quantity'         => 1,
            'currency'         => 'USD',
        ]);

        $auth = $this->service->createOrderForIntent($intent, 'idempotent-key-001');

        $this->assertSame('ORDER_111', $auth->order_id);
        $this->assertSame(PaymentAuthorizationStatus::CREATED, $auth->status);
        $this->assertSame(5000, $auth->amount_cents);

        // Idempotency: second call returns the same authorization without calling PayPal API again
        $auth2 = $this->service->createOrderForIntent($intent, 'idempotent-key-001');
        $this->assertSame($auth->id, $auth2->id);

        Http::assertSentCount(2); // 1 token + 1 create order
    }

    public function test_process_authorization_clean_match_captures_immediately(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_222/authorize' => Http::response([
                'id'             => 'ORDER_222',
                'status'         => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id'     => 'AUTH_AAA',
                                    'status' => 'CREATED',
                                ],
                            ],
                        ],
                    ],
                ],
                'payer' => ['payer_id' => 'PAYER_123'],
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_AAA/capture' => Http::response([
                'id'     => 'CAP_BBB',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $merchant = Merchant::factory()->create(['compliance_score' => 100.00]);
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'customer_id'      => $user->id,
            'unit_price_cents' => 9999,
            'quantity'         => 1,
            'currency'         => 'USD',
            'product_name'     => 'Apple iPhone 16 Pro',
            'specification_json' => ['brand' => 'Apple', 'model' => 'iPhone 16 Pro', 'condition' => 'new'],
        ]);

        $checkoutPayload = [
            'merchant_id'       => $merchant->id,
            'currency'          => 'USD',
            'unit_price_cents'  => 9999,
            'quantity'          => 1,
            'total_price_cents' => 9999,
            'product_name'      => 'Apple iPhone 16 Pro',
            'specification_json' => ['brand' => 'Apple', 'model' => 'iPhone 16 Pro', 'condition' => 'new'],
        ];

        $paymentAuth = $this->service->processAuthorization($intent, 'ORDER_222', $checkoutPayload);

        $this->assertSame(PaymentAuthorizationStatus::CAPTURED, $paymentAuth->status);
        $this->assertSame('AUTH_AAA', $paymentAuth->authorization_id);
        $this->assertSame('CAP_BBB', $paymentAuth->capture_id);
        $this->assertSame(PurchaseIntentStatus::VERIFIED, $intent->fresh()->status);
        $this->assertNotNull($paymentAuth->verification_decision_id);
    }

    public function test_process_authorization_mismatch_voids_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_333/authorize' => Http::response([
                'id'             => 'ORDER_333',
                'status'         => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id'     => 'AUTH_VOID_ME',
                                    'status' => 'CREATED',
                                ],
                            ],
                        ],
                    ],
                ],
                'payer' => ['payer_id' => 'PAYER_123'],
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_VOID_ME/void' => Http::response(null, 204),
        ]);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'customer_id'      => $user->id,
            'unit_price_cents' => 5000,
            'quantity'         => 1,
            'currency'         => 'USD',
        ]);

        // Severe price surge discrepancy (+60%)
        $checkoutPayload = [
            'merchant_id'       => $merchant->id,
            'currency'          => 'USD',
            'unit_price_cents'  => 8000,
            'quantity'          => 1,
            'total_price_cents' => 8000,
        ];

        $paymentAuth = $this->service->processAuthorization($intent, 'ORDER_333', $checkoutPayload);

        $this->assertSame(PaymentAuthorizationStatus::VOIDED, $paymentAuth->status);
        $this->assertSame('AUTH_VOID_ME', $paymentAuth->authorization_id);
        $this->assertNull($paymentAuth->capture_id);
        $this->assertNotNull($paymentAuth->voided_at);
        $this->assertSame(PurchaseIntentStatus::MISMATCHED, $intent->fresh()->status);
    }

    public function test_process_authorization_discrepancy_holds_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_444/authorize' => Http::response([
                'id'             => 'ORDER_444',
                'status'         => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id'     => 'AUTH_HOLD_ME',
                                    'status' => 'CREATED',
                                ],
                            ],
                        ],
                    ],
                ],
                'payer' => ['payer_id' => 'PAYER_123'],
            ], 200),
        ]);

        $merchant = Merchant::factory()->create(['compliance_score' => 100.00]);
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id'      => $merchant->id,
            'customer_id'      => $user->id,
            'unit_price_cents' => 5000,
            'quantity'         => 1,
            'currency'         => 'USD',
            'specification_json' => ['brand' => 'Sony', 'colour' => 'black'],
            'allowed_substitutions_json' => ['colour' => false],
        ]);

        // Colour mismatch without tolerance produces REQUIRE_CUSTOMER_CONFIRMATION -> CAPTURE_HOLD
        $checkoutPayload = [
            'merchant_id'       => $merchant->id,
            'currency'          => 'USD',
            'unit_price_cents'  => 5000,
            'quantity'          => 1,
            'total_price_cents' => 5000,
            'specification_json' => ['brand' => 'Sony', 'colour' => 'silver'],
        ];

        $paymentAuth = $this->service->processAuthorization($intent, 'ORDER_444', $checkoutPayload);

        $this->assertSame(PaymentAuthorizationStatus::HELD, $paymentAuth->status);
        $this->assertSame('AUTH_HOLD_ME', $paymentAuth->authorization_id);
        $this->assertNull($paymentAuth->capture_id);
        $this->assertTrue($paymentAuth->verificationDecision->hold_payment_capture);
    }

    public function test_execute_held_capture_captures_held_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_HOLD_1/capture' => Http::response([
                'id'     => 'CAP_AFTER_HOLD',
                'status' => 'COMPLETED',
            ], 201),
        ]);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $user->id,
        ]);

        $paymentAuth = PaymentAuthorization::factory()->held()->create([
            'purchase_intent_id' => $intent->id,
            'authorization_id'   => 'AUTH_HOLD_1',
            'amount_cents'       => 5000,
            'currency'           => 'USD',
        ]);

        $released = $this->service->executeHeldCapture($paymentAuth, $user, 'Customer approved alternate color.');

        $this->assertSame(PaymentAuthorizationStatus::CAPTURED, $released->status);
        $this->assertSame('CAP_AFTER_HOLD', $released->capture_id);
        $this->assertNotNull($released->captured_at);
    }

    public function test_execute_held_void_voids_held_authorization(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token',
                'expires_in'   => 3600,
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/payments/authorizations/AUTH_HOLD_2/void' => Http::response(null, 204),
        ]);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $user->id,
        ]);

        $paymentAuth = PaymentAuthorization::factory()->held()->create([
            'purchase_intent_id' => $intent->id,
            'authorization_id'   => 'AUTH_HOLD_2',
            'amount_cents'       => 5000,
            'currency'           => 'USD',
        ]);

        $voided = $this->service->executeHeldVoid($paymentAuth, $user, 'Customer rejected discrepancy.');

        $this->assertSame(PaymentAuthorizationStatus::VOIDED, $voided->status);
        $this->assertNotNull($voided->voided_at);
    }
}
