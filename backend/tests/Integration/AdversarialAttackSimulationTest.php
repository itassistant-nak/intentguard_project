<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Enums\PaymentAuthorizationStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\SystemAuditEvent;
use App\Enums\VerificationVerdict;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\SystemAuditLog;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Audit\AuditLogService;
use App\Services\PayPal\PayPalClient;
use App\Services\PayPal\PayPalGatewayService;
use App\Services\PurchaseIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AdversarialAttackSimulationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Vector 1: Direct database tampering of a purchase intent payload is mathematically detected.
     */
    public function test_adversarial_database_payload_tampering_is_detected(): void
    {
        $user = User::factory()->customer()->create();
        $merchant = Merchant::factory()->verified()->create();

        $intentService = app(PurchaseIntentService::class);
        $intent = $intentService->record($user, $merchant, [
            'merchant_id' => $merchant->id,
            'product_name' => 'High-End Smartphone',
            'product_sku' => 'PHONE-512GB-BLK',
            'quantity' => 1,
            'unit_price_cents' => 129900,
            'currency' => 'USD',
            'specification_json' => [
                'brand' => 'Acme',
                'model' => 'Pro Max',
                'condition' => 'new',
            ],
            'shipping_conditions_json' => [
                'method' => 'Express Courier',
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'shipping_cost_cents' => 2500,
            ],
        ]);

        // Baseline verification: intact
        $this->assertTrue($intentService->verifyIntegrity($intent));

        // ADVERSARY: Manipulate unit price in database directly
        $intent->unit_price_cents = 159900;
        $intent->saveQuietly();

        // Tamper verification: mathematically fails
        $fresh = PurchaseIntent::findOrFail($intent->id);
        $this->assertFalse($intentService->verifyIntegrity($fresh));
    }

    /**
     * Vector 2: Bait-and-switch checkout price surge is detected pre-capture and payment is voided.
     */
    public function test_adversarial_bait_and_switch_price_surge_triggers_immediate_payment_void(): void
    {
        $user = User::factory()->customer()->create();
        $merchant = Merchant::factory()->verified()->create();

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $user->id,
            'merchant_id' => $merchant->id,
            'unit_price_cents' => 39900, // $399.00
            'quantity' => 1,
            'currency' => 'USD',
            'specification_json' => ['brand' => 'Sony', 'condition' => 'new'],
            'shipping_conditions_json' => [
                'method' => 'Standard Tracked Courier',
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'shipping_cost_cents' => 1500,
            ],
            'status' => PurchaseIntentStatus::PENDING,
        ]);

        $mockClient = Mockery::mock(PayPalClient::class);
        $mockClient->shouldReceive('authorizeOrder')
            ->once()
            ->andReturn([
                'id' => 'ORDER-SURGE-123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id' => 'AUTH-SURGE-456',
                                    'status' => 'CREATED',
                                    'amount' => ['value' => '464.00', 'currency_code' => 'USD'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        // SPIE evaluates $449.00 unit price vs $399.00 intent, verdict is MISMATCH, so gateway voids
        $mockClient->shouldReceive('voidAuthorization')
            ->once()
            ->with('AUTH-SURGE-456')
            ->andReturn(['status' => 'VOIDED']);

        $this->app->instance(PayPalClient::class, $mockClient);
        $gateway = app(PayPalGatewayService::class);

        // Merchant attempts to submit checkout with a $50 price surge ($449.00 unit price)
        $auth = $gateway->processAuthorization(
            $intent,
            'ORDER-SURGE-123',
            [
                'merchant_id' => $merchant->id,
                'currency' => 'USD',
                'unit_price_cents' => 44900,
                'quantity' => 1,
                'total_price_cents' => 46400,
                'shipping_cost_cents' => 1500,
                'product_name' => $intent->product_name,
            ]
        );

        $this->assertSame(PaymentAuthorizationStatus::VOIDED, $auth->status);
        $this->assertSame(VerificationVerdict::MISMATCH, $auth->verificationDecision->verdict);
        $this->assertNotNull($auth->voided_at);

        // Intent status updated to mismatched
        $this->assertSame(PurchaseIntentStatus::MISMATCHED, $intent->fresh()->status);
    }

    /**
     * Vector 3: Unauthorized refurbished item substitution is intercepted and held/voided.
     */
    public function test_adversarial_unauthorized_refurbished_substitution_is_intercepted(): void
    {
        $user = User::factory()->customer()->create();
        $merchant = Merchant::factory()->verified()->create();

        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $user->id,
            'merchant_id' => $merchant->id,
            'unit_price_cents' => 89900,
            'quantity' => 1,
            'currency' => 'USD',
            'specification_json' => [
                'brand' => 'Apple',
                'model' => 'iPhone 15',
                'condition' => 'new',
            ],
            'allowed_substitutions_json' => [
                'refurbished_ok' => false,
            ],
            'shipping_conditions_json' => [
                'method' => 'Standard Tracked Courier',
                'estimated_days_min' => 2,
                'estimated_days_max' => 3,
                'shipping_cost_cents' => 1000,
            ],
            'status' => PurchaseIntentStatus::PENDING,
        ]);

        $mockClient = Mockery::mock(PayPalClient::class);
        $mockClient->shouldReceive('authorizeOrder')
            ->once()
            ->andReturn([
                'id' => 'ORDER-SUB-123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'authorizations' => [
                                [
                                    'id' => 'AUTH-SUB-456',
                                    'status' => 'CREATED',
                                    'amount' => ['value' => '909.00', 'currency_code' => 'USD'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        // Fulfilling refurbished when strict 'new' was ordered triggers MISMATCH -> instant void
        $mockClient->shouldReceive('voidAuthorization')
            ->once()
            ->with('AUTH-SUB-456')
            ->andReturn(['status' => 'VOIDED']);

        $this->app->instance(PayPalClient::class, $mockClient);
        $gateway = app(PayPalGatewayService::class);

        $auth = $gateway->processAuthorization(
            $intent,
            'ORDER-SUB-123',
            [
                'merchant_id' => $merchant->id,
                'currency' => 'USD',
                'unit_price_cents' => 89900,
                'quantity' => 1,
                'total_price_cents' => 90900,
                'shipping_cost_cents' => 1000,
                'product_name' => $intent->product_name,
                'specification_json' => [
                    'brand' => 'Apple',
                    'model' => 'iPhone 15',
                    'condition' => 'refurbished',
                ],
            ]
        );

        $this->assertSame(PaymentAuthorizationStatus::VOIDED, $auth->status);
        $this->assertSame(PurchaseIntentStatus::MISMATCHED, $intent->fresh()->status);
    }

    /**
     * Vector 4: Forged webhook signature is rejected with HTTP 400.
     */
    public function test_adversarial_forged_webhook_signature_is_rejected(): void
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
            'id'         => 'WH-EVENT-FORGED-99',
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
            'event_id'           => 'WH-EVENT-FORGED-99',
            'signature_verified' => false,
            'status'             => 'failed',
        ]);
    }

    /**
     * Vector 5: Webhook replay attack is deduplicated and safely ignored.
     */
    public function test_adversarial_webhook_replay_attack_is_deduplicated(): void
    {
        WebhookEvent::factory()->create([
            'event_id'           => 'WH-REPLAY-999',
            'signature_verified' => true,
            'status'             => 'processed',
        ]);

        $payload = [
            'id'         => 'WH-REPLAY-999',
            'event_type' => 'PAYMENT.AUTHORIZATION.CREATED',
        ];

        // Deduplication returns existing processed event without re-verifying
        $response = $this->postJson('/api/v1/webhooks/paypal', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.event_id', 'WH-REPLAY-999');

        // Exactly one webhook record exists in database
        $this->assertSame(1, WebhookEvent::where('event_id', 'WH-REPLAY-999')->count());
    }

    /**
     * Vector 6: Historical system audit log tampering is detected by linear SHA-256 hash chaining.
     */
    public function test_adversarial_audit_log_tampering_is_detected_by_linear_chain(): void
    {
        $auditService = app(AuditLogService::class);

        // Record a chain of 4 events
        $log1 = $auditService->record(SystemAuditEvent::INTENT_RECORDED, null, null, ['actor_type' => 'system']);
        $log2 = $auditService->record(SystemAuditEvent::VERIFICATION_DECIDED, null, null, ['actor_type' => 'system']);
        $log3 = $auditService->record(SystemAuditEvent::PAYMENT_AUTHORIZED, null, null, ['actor_type' => 'system']);
        $log4 = $auditService->record(SystemAuditEvent::PAYMENT_CAPTURED, null, null, ['actor_type' => 'system']);

        // Initial chain verification: intact
        $initialCheck = $auditService->verifyChain(10);
        $this->assertTrue($initialCheck['is_intact']);
        $this->assertSame(4, $initialCheck['verified_count']);

        // ATTACK: Malicious actor tampers with event name of record #2
        SystemAuditLog::where('id', $log2->id)->update([
            'event' => 'payment.authorized',
        ]);

        // Linear chain traversal detects tampering at record #2
        $tamperedCheck = $auditService->verifyChain(10);
        $this->assertFalse($tamperedCheck['is_intact']);
        $this->assertSame($log2->id, $tamperedCheck['broken_record_id']);
        $this->assertStringContainsString("Tampering detected at record #{$log2->id}", $tamperedCheck['message']);
    }
}
