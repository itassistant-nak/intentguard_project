<?php

declare(strict_types=1);

namespace App\Services\PayPal;

use App\Engines\SemanticIntegrityEngine\SemanticIntegrityEngine;
use App\Engines\TransactionDecisionEngine\TransactionDecisionEngine;
use App\Enums\CaptureAction;
use App\Enums\PaymentAuthorizationStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\VerificationVerdict;
use App\Models\PaymentAuthorization;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayPalGatewayService
{
    public function __construct(
        private readonly PayPalClient $client,
        private readonly SemanticIntegrityEngine $integrityEngine,
        private readonly TransactionDecisionEngine $decisionEngine,
    ) {}

    /**
     * Initializes a PayPal Orders v2 resource for an established purchase intent.
     * Enforces idempotency to prevent duplicate order generation.
     *
     * @param array<string, mixed> $options
     */
    public function createOrderForIntent(
        PurchaseIntent $intent,
        string $idempotencyKey,
        array $options = [],
    ): PaymentAuthorization {
        // Idempotency: return existing authorization if already created for this key
        $existing = PaymentAuthorization::where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $existing;
        }

        $orderData = $this->client->createOrder($intent, $options);
        $orderId = (string) ($orderData['id'] ?? '');

        if ($orderId === '') {
            throw new \RuntimeException("PayPal order creation did not return an order ID.");
        }

        return PaymentAuthorization::create([
            'purchase_intent_id' => $intent->id,
            'gateway'            => 'paypal',
            'order_id'           => $orderId,
            'amount_cents'       => $intent->totalPriceCents(),
            'currency'           => $intent->currency,
            'status'             => PaymentAuthorizationStatus::CREATED,
            'idempotency_key'    => $idempotencyKey,
            'metadata_json'      => $orderData,
        ]);
    }

    /**
     * Authorizes an approved PayPal order and evaluates checkout payload with TDE.
     * Intercepts payment capture:
     * - CAPTURE_IMMEDIATE: Captures authorization immediately on PayPal
     * - CAPTURE_HOLD: Holds payment authorization uncaptured, awaiting customer confirmation
     * - CAPTURE_VOID: Voids payment authorization immediately on PayPal
     *
     * @param array<string, mixed> $checkoutPayload
     */
    public function processAuthorization(
        PurchaseIntent $intent,
        string $orderId,
        array $checkoutPayload,
        ?string $idempotencyKey = null,
    ): PaymentAuthorization {
        return DB::transaction(function () use ($intent, $orderId, $checkoutPayload, $idempotencyKey) {
            // Find or initialize authorization record
            $paymentAuth = PaymentAuthorization::where('order_id', $orderId)->first();

            if ($paymentAuth === null) {
                $paymentAuth = PaymentAuthorization::create([
                    'purchase_intent_id' => $intent->id,
                    'gateway'            => 'paypal',
                    'order_id'           => $orderId,
                    'amount_cents'       => (int) ($checkoutPayload['total_price_cents'] ?? $intent->totalPriceCents()),
                    'currency'           => (string) ($checkoutPayload['currency'] ?? $intent->currency),
                    'status'             => PaymentAuthorizationStatus::CREATED,
                    'idempotency_key'    => $idempotencyKey ?? ('auth_' . uniqid() . '_' . $orderId),
                ]);
            }

            // Step 1: Call PayPal to authorize the approved order
            $authData = $this->client->authorizeOrder($orderId);

            // Extract Payments v2 authorization ID
            $authorizationId = (string) (
                $authData['purchase_units'][0]['payments']['authorizations'][0]['id']
                ?? $authData['id']
                ?? ''
            );

            if ($authorizationId === '') {
                throw new \RuntimeException("PayPal order authorization failed: no authorization ID returned.");
            }

            $payerId = (string) ($authData['payer']['payer_id'] ?? null);

            // Step 2: Run Semantic Purchase Integrity Engine
            $report = $this->integrityEngine->verify($intent, $checkoutPayload);

            // Step 3: Run Transaction Decision Engine
            $merchantScore = (float) ($intent->merchant->compliance_score ?? 100.00);
            $directive = $this->decisionEngine->determineCaptureAction($report->verdict, $merchantScore);
            $holdCapture = ($directive === CaptureAction::CAPTURE_HOLD);

            // Step 4: Persist Verification Decision
            $decision = VerificationDecision::create([
                'purchase_intent_id'     => $intent->id,
                'checkout_payload_json'  => $checkoutPayload,
                'discrepancy_flags_json' => array_map(fn ($d) => $d->toArray(), $report->discrepancies),
                'confidence_score'       => $report->confidenceScore,
                'verdict'                => $report->verdict,
                'hold_payment_capture'   => $holdCapture,
                'decided_at'             => Carbon::now(),
            ]);

            // Step 5: Execute Interception Directive
            $now = Carbon::now();

            if ($directive === CaptureAction::CAPTURE_IMMEDIATE) {
                // Clean MATCH: Capture immediately
                $captureData = $this->client->captureAuthorization(
                    $authorizationId,
                    $paymentAuth->amount_cents,
                    $paymentAuth->currency
                );

                $captureId = (string) ($captureData['id'] ?? null);

                $paymentAuth->update([
                    'authorization_id'         => $authorizationId,
                    'capture_id'               => $captureId,
                    'customer_payer_id'        => $payerId,
                    'verification_decision_id' => $decision->id,
                    'status'                   => PaymentAuthorizationStatus::CAPTURED,
                    'authorized_at'            => $now,
                    'captured_at'              => $now,
                    'metadata_json'            => array_merge($paymentAuth->metadata_json ?? [], [
                        'authorization_response' => $authData,
                        'capture_response'       => $captureData,
                    ]),
                ]);

                $intent->update(['status' => PurchaseIntentStatus::VERIFIED]);
            } elseif ($directive === CaptureAction::CAPTURE_HOLD) {
                // Discrepancy requiring confirmation / compliance hold: Hold authorization uncaptured
                $paymentAuth->update([
                    'authorization_id'         => $authorizationId,
                    'customer_payer_id'        => $payerId,
                    'verification_decision_id' => $decision->id,
                    'status'                   => PaymentAuthorizationStatus::HELD,
                    'authorized_at'            => $now,
                    'metadata_json'            => array_merge($paymentAuth->metadata_json ?? [], [
                        'authorization_response' => $authData,
                    ]),
                ]);

                // Record initial hold audit trail in TDE
                $this->decisionEngine->recordInitialHold($decision);

                if ($intent->status === PurchaseIntentStatus::PENDING) {
                    $intent->update(['status' => PurchaseIntentStatus::CONFIRMED]);
                }
            } else {
                // Severe MISMATCH: Void authorization immediately
                $voidData = $this->client->voidAuthorization($authorizationId);

                $paymentAuth->update([
                    'authorization_id'         => $authorizationId,
                    'customer_payer_id'        => $payerId,
                    'verification_decision_id' => $decision->id,
                    'status'                   => PaymentAuthorizationStatus::VOIDED,
                    'authorized_at'            => $now,
                    'voided_at'                => $now,
                    'metadata_json'            => array_merge($paymentAuth->metadata_json ?? [], [
                        'authorization_response' => $authData,
                        'void_response'          => $voidData,
                    ]),
                ]);

                $intent->update(['status' => PurchaseIntentStatus::MISMATCHED]);
            }

            return $paymentAuth->fresh(['purchaseIntent', 'verificationDecision']);
        });
    }

    /**
     * Executes payment capture for a held transaction upon verified customer/admin confirmation.
     */
    public function executeHeldCapture(
        PaymentAuthorization $paymentAuth,
        User $actor,
        string $reason = 'Customer approved held transaction.',
    ): PaymentAuthorization {
        if (!$paymentAuth->isHeld()) {
            throw new \DomainException("Payment authorization #{$paymentAuth->id} is not currently in HELD status.");
        }

        if (empty($paymentAuth->authorization_id)) {
            throw new \DomainException("Payment authorization #{$paymentAuth->id} has no valid authorization ID.");
        }

        return DB::transaction(function () use ($paymentAuth, $actor, $reason) {
            $captureData = $this->client->captureAuthorization(
                $paymentAuth->authorization_id,
                $paymentAuth->amount_cents,
                $paymentAuth->currency
            );

            $captureId = (string) ($captureData['id'] ?? null);
            $now = Carbon::now();

            $paymentAuth->update([
                'capture_id'    => $captureId,
                'status'        => PaymentAuthorizationStatus::CAPTURED,
                'captured_at'   => $now,
                'metadata_json' => array_merge($paymentAuth->metadata_json ?? [], [
                    'released_capture_response' => $captureData,
                ]),
            ]);

            // Resolve linked verification decision if present
            if ($paymentAuth->verification_decision_id !== null) {
                $decision = $paymentAuth->verificationDecision;
                if ($decision !== null && !$decision->isResolved()) {
                    $this->decisionEngine->resolveCustomerConfirmation($decision, $actor, $reason);
                }
            }

            return $paymentAuth->fresh(['purchaseIntent', 'verificationDecision']);
        });
    }

    /**
     * Voids an authorized payment for a held transaction upon customer rejection.
     */
    public function executeHeldVoid(
        PaymentAuthorization $paymentAuth,
        User $actor,
        string $reason = 'Customer rejected discrepancy in held transaction.',
    ): PaymentAuthorization {
        if (!$paymentAuth->isHeld()) {
            throw new \DomainException("Payment authorization #{$paymentAuth->id} is not currently in HELD status.");
        }

        if (empty($paymentAuth->authorization_id)) {
            throw new \DomainException("Payment authorization #{$paymentAuth->id} has no valid authorization ID.");
        }

        return DB::transaction(function () use ($paymentAuth, $actor, $reason) {
            $voidData = $this->client->voidAuthorization($paymentAuth->authorization_id);
            $now = Carbon::now();

            $paymentAuth->update([
                'status'        => PaymentAuthorizationStatus::VOIDED,
                'voided_at'     => $now,
                'metadata_json' => array_merge($paymentAuth->metadata_json ?? [], [
                    'released_void_response' => $voidData,
                ]),
            ]);

            // Resolve linked verification decision if present
            if ($paymentAuth->verification_decision_id !== null) {
                $decision = $paymentAuth->verificationDecision;
                if ($decision !== null && !$decision->isResolved()) {
                    $this->decisionEngine->resolveCustomerRejection($decision, $actor, $reason);
                }
            }

            return $paymentAuth->fresh(['purchaseIntent', 'verificationDecision']);
        });
    }
}
