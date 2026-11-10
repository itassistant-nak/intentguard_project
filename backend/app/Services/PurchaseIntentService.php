<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PurchaseIntentStatus;
use App\Models\Merchant;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Support\Carbon;

class PurchaseIntentService
{
    /**
     * Default TTL in hours for a pending intent.
     */
    private const DEFAULT_TTL_HOURS = 24;

    /**
     * Record a new purchase intent from a customer.
     *
     * Builds a canonical, deterministic SHA-256 hash of the entire intent payload
     * so that any subsequent tamper of the recorded intent can be detected.
     *
     * @param  User     $customer
     * @param  Merchant $merchant
     * @param  array<string, mixed> $data   Validated payload from RecordPurchaseIntentRequest
     * @return PurchaseIntent
     */
    public function record(User $customer, Merchant $merchant, array $data): PurchaseIntent
    {
        $expiresAt = Carbon::now()->addHours(self::DEFAULT_TTL_HOURS)->startOfSecond();

        // Build canonical payload for hashing (sorted keys for determinism)
        $canonical = $this->buildCanonicalPayload(
            customerId:            $customer->id,
            merchantId:            $merchant->id,
            productName:           $data['product_name'],
            productSku:            $data['product_sku'] ?? null,
            productUrl:            $data['product_url'] ?? null,
            specificationJson:     $data['specification_json'],
            quantity:              (int) $data['quantity'],
            unitPriceCents:        (int) $data['unit_price_cents'],
            currency:              strtoupper($data['currency']),
            shippingConditions:    $data['shipping_conditions_json'],
            allowedSubstitutions:  $data['allowed_substitutions_json'] ?? null,
            expiresAt:             $expiresAt->toISOString(),
        );

        $intentHash = hash('sha256', $canonical);

        return PurchaseIntent::create([
            'customer_id'               => $customer->id,
            'merchant_id'               => $merchant->id,
            'product_name'              => $data['product_name'],
            'product_sku'               => $data['product_sku'] ?? null,
            'product_url'               => $data['product_url'] ?? null,
            'specification_json'        => $data['specification_json'],
            'quantity'                  => (int) $data['quantity'],
            'unit_price_cents'          => (int) $data['unit_price_cents'],
            'currency'                  => strtoupper($data['currency']),
            'shipping_conditions_json'  => $data['shipping_conditions_json'],
            'allowed_substitutions_json'=> $data['allowed_substitutions_json'] ?? null,
            'intent_hash'               => $intentHash,
            'status'                    => PurchaseIntentStatus::PENDING,
            'expires_at'                => $expiresAt,
        ]);
    }

    /**
     * Find a purchase intent by its public intent_hash or internal ID.
     *
     * @param  string|int $identifier
     * @return PurchaseIntent|null
     */
    public function find(string|int $identifier): ?PurchaseIntent
    {
        if (is_numeric($identifier)) {
            return PurchaseIntent::with(['customer', 'merchant'])->find((int) $identifier);
        }

        return PurchaseIntent::with(['customer', 'merchant'])
            ->where('intent_hash', $identifier)
            ->first();
    }

    /**
     * Cancel an active purchase intent.
     *
     * @param  PurchaseIntent $intent
     * @param  string         $reason
     * @return PurchaseIntent
     *
     * @throws \RuntimeException if intent is already in a terminal state
     */
    public function cancel(PurchaseIntent $intent, string $reason = 'Cancelled by customer'): PurchaseIntent
    {
        if (!$intent->isActive()) {
            throw new \RuntimeException(
                "Cannot cancel intent #{$intent->id}: already in terminal state [{$intent->status->value}]."
            );
        }

        $intent->update([
            'status' => PurchaseIntentStatus::CANCELLED,
        ]);

        return $intent->fresh();
    }

    /**
     * Expire overdue pending intents (run via scheduler).
     *
     * @return int Number of intents expired
     */
    public function expireOverdue(): int
    {
        return PurchaseIntent::where('status', PurchaseIntentStatus::PENDING)
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => PurchaseIntentStatus::EXPIRED]);
    }

    /**
     * Verifies that the given hash matches the recomputed canonical payload,
     * providing tamper-evidence for the stored intent.
     *
     * @param  PurchaseIntent $intent
     * @return bool
     */
    public function verifyIntegrity(PurchaseIntent $intent): bool
    {
        $canonical = $this->buildCanonicalPayload(
            customerId:            $intent->customer_id,
            merchantId:            $intent->merchant_id,
            productName:           $intent->product_name,
            productSku:            $intent->product_sku,
            productUrl:            $intent->product_url,
            specificationJson:     $intent->specification_json,
            quantity:              $intent->quantity,
            unitPriceCents:        $intent->unit_price_cents,
            currency:              $intent->currency,
            shippingConditions:    $intent->shipping_conditions_json,
            allowedSubstitutions:  $intent->allowed_substitutions_json,
            expiresAt:             $intent->expires_at?->toISOString() ?? '',
        );

        return hash_equals($intent->intent_hash, hash('sha256', $canonical));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a deterministic canonical JSON string from intent fields.
     * Keys are sorted alphabetically to ensure consistent hashing.
     */
    private function buildCanonicalPayload(
        int     $customerId,
        int     $merchantId,
        string  $productName,
        ?string $productSku,
        ?string $productUrl,
        array   $specificationJson,
        int     $quantity,
        int     $unitPriceCents,
        string  $currency,
        array   $shippingConditions,
        ?array  $allowedSubstitutions,
        string  $expiresAt,
    ): string {
        $payload = [
            'allowed_substitutions_json' => $allowedSubstitutions,
            'currency'                   => $currency,
            'customer_id'                => $customerId,
            'expires_at'                 => $expiresAt,
            'merchant_id'                => $merchantId,
            'product_name'               => $productName,
            'product_sku'                => $productSku,
            'product_url'                => $productUrl,
            'quantity'                   => $quantity,
            'shipping_conditions_json'   => $shippingConditions,
            'specification_json'         => $specificationJson,
            'unit_price_cents'           => $unitPriceCents,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
