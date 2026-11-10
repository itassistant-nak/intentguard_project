<?php

declare(strict_types=1);

namespace App\Engines\SemanticIntegrityEngine;

use App\Enums\DiscrepancyCategory;
use App\Enums\DiscrepancySeverity;
use App\Models\PurchaseIntent;

class DeterministicEvaluator
{
    /**
     * Evaluates exact financial, scalar, and identity rules between the intent and checkout payload.
     *
     * @param  PurchaseIntent       $intent
     * @param  array<string, mixed> $checkout
     * @return list<Discrepancy>
     */
    public function evaluate(PurchaseIntent $intent, array $checkout): array
    {
        $discrepancies = [];

        // 1. Merchant Identity Evaluation
        $checkoutMerchantId = (int) ($checkout['merchant_id'] ?? 0);
        if ($checkoutMerchantId !== $intent->merchant_id) {
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::MERCHANT,
                severity:      DiscrepancySeverity::CRITICAL,
                field:         'merchant_id',
                intendedValue: $intent->merchant_id,
                actualValue:   $checkoutMerchantId,
                message:       "Merchant mismatch: Intent is bound to merchant #{$intent->merchant_id}, but checkout attempted by merchant #{$checkoutMerchantId}.",
            );
        }

        // 2. Currency Code Evaluation
        $checkoutCurrency = strtoupper((string) ($checkout['currency'] ?? ''));
        $intendedCurrency = strtoupper($intent->currency);
        if ($checkoutCurrency !== $intendedCurrency) {
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::CURRENCY,
                severity:      DiscrepancySeverity::CRITICAL,
                field:         'currency',
                intendedValue: $intendedCurrency,
                actualValue:   $checkoutCurrency,
                message:       "Currency mismatch: Expected [{$intendedCurrency}], received [{$checkoutCurrency}].",
            );
        }

        // 3. Quantity Evaluation
        $checkoutQuantity = (int) ($checkout['quantity'] ?? ($checkout['line_items'][0]['quantity'] ?? 0));
        if ($checkoutQuantity !== $intent->quantity) {
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::QUANTITY,
                severity:      DiscrepancySeverity::CRITICAL,
                field:         'quantity',
                intendedValue: $intent->quantity,
                actualValue:   $checkoutQuantity,
                message:       "Quantity mismatch: Expected {$intent->quantity} unit(s), received {$checkoutQuantity} unit(s).",
            );
        }

        // 4. Unit Price Evaluation
        $checkoutUnitPrice = (int) ($checkout['unit_price_cents'] ?? ($checkout['line_items'][0]['unit_price_cents'] ?? 0));
        if ($checkoutUnitPrice > $intent->unit_price_cents) {
            $diffCents = $checkoutUnitPrice - $intent->unit_price_cents;
            $diffFormatted = number_format($diffCents / 100, 2);
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::PRICE,
                severity:      DiscrepancySeverity::CRITICAL,
                field:         'unit_price_cents',
                intendedValue: $intent->unit_price_cents,
                actualValue:   $checkoutUnitPrice,
                message:       "Unauthorized price surge: Unit price is \${$diffFormatted} higher than intended amount.",
            );
        } elseif ($checkoutUnitPrice < $intent->unit_price_cents && $checkoutUnitPrice > 0) {
            // Favorable discount
            $diffCents = $intent->unit_price_cents - $checkoutUnitPrice;
            $diffFormatted = number_format($diffCents / 100, 2);
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::PRICE,
                severity:      DiscrepancySeverity::INFO,
                field:         'unit_price_cents',
                intendedValue: $intent->unit_price_cents,
                actualValue:   $checkoutUnitPrice,
                message:       "Discount detected: Checkout unit price is \${$diffFormatted} lower than agreed price.",
                isTolerated:   true,
            );
        }

        // 5. Shipping Cost / Fee Surge Evaluation
        $intendedShippingCost = (int) ($intent->shipping_conditions_json['shipping_cost_cents'] ?? 0);
        $checkoutShippingCost = (int) ($checkout['shipping_cost_cents'] ?? ($checkout['shipping_fee_cents'] ?? 0));
        if ($checkoutShippingCost > $intendedShippingCost) {
            $feeSurge = $checkoutShippingCost - $intendedShippingCost;
            $surgeFormatted = number_format($feeSurge / 100, 2);
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::SHIPPING,
                severity:      DiscrepancySeverity::CRITICAL,
                field:         'shipping_cost_cents',
                intendedValue: $intendedShippingCost,
                actualValue:   $checkoutShippingCost,
                message:       "Hidden fee surge: Shipping cost increased by \${$surgeFormatted} above agreed terms.",
            );
        }

        // 6. SKU Evaluation (if both provide a SKU)
        $intendedSku = $intent->product_sku ? strtoupper(trim($intent->product_sku)) : null;
        $checkoutSku = !empty($checkout['product_sku'])
            ? strtoupper(trim((string) $checkout['product_sku']))
            : (!empty($checkout['line_items'][0]['product_sku']) ? strtoupper(trim((string) $checkout['line_items'][0]['product_sku'])) : null);

        if ($intendedSku !== null && $checkoutSku !== null && $intendedSku !== $checkoutSku) {
            $discrepancies[] = new Discrepancy(
                category:      DiscrepancyCategory::SPECIFICATION,
                severity:      DiscrepancySeverity::WARNING,
                field:         'product_sku',
                intendedValue: $intendedSku,
                actualValue:   $checkoutSku,
                message:       "SKU deviation: Expected SKU [{$intendedSku}], received [{$checkoutSku}].",
            );
        }

        return $discrepancies;
    }
}
