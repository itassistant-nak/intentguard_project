<?php

declare(strict_types=1);

namespace App\Engines\SemanticIntegrityEngine;

use App\Enums\DiscrepancyCategory;
use App\Enums\DiscrepancySeverity;
use App\Models\PurchaseIntent;

class SemanticAttributeEvaluator
{
    /**
     * Evaluates descriptive attributes, product condition, and customer substitution rules.
     *
     * @param  PurchaseIntent       $intent
     * @param  array<string, mixed> $checkout
     * @return list<Discrepancy>
     */
    public function evaluate(PurchaseIntent $intent, array $checkout): array
    {
        $discrepancies = [];

        $intentSpec = $intent->specification_json ?? [];
        $checkoutSpec = $checkout['specification_json']
            ?? ($checkout['line_items'][0]['specification_json'] ?? []);

        $allowedSubs = $intent->allowed_substitutions_json ?? [];

        // 1. Product Condition Evaluation
        $intendedCondition = strtolower((string) ($intentSpec['condition'] ?? 'new'));
        $checkoutCondition = strtolower((string) ($checkoutSpec['condition'] ?? ($checkout['condition'] ?? 'new')));

        if ($intendedCondition !== $checkoutCondition) {
            $isRefurbishedTolerated = ($intendedCondition === 'new')
                && in_array($checkoutCondition, ['refurbished', 'open_box', 'used'], true)
                && (!empty($allowedSubs['refurbished_ok']));

            if ($isRefurbishedTolerated) {
                $discrepancies[] = new Discrepancy(
                    category:      DiscrepancyCategory::CONDITION,
                    severity:      DiscrepancySeverity::INFO,
                    field:         'condition',
                    intendedValue: $intendedCondition,
                    actualValue:   $checkoutCondition,
                    message:       "Product condition altered from [{$intendedCondition}] to [{$checkoutCondition}], permitted by customer substitution policy.",
                    isTolerated:   true,
                );
            } else {
                $discrepancies[] = new Discrepancy(
                    category:      DiscrepancyCategory::CONDITION,
                    severity:      DiscrepancySeverity::CRITICAL,
                    field:         'condition',
                    intendedValue: $intendedCondition,
                    actualValue:   $checkoutCondition,
                    message:       "Product condition violation: Customer intended [{$intendedCondition}], but checkout item is [{$checkoutCondition}].",
                    isTolerated:   false,
                );
            }
        }

        // 2. Brand Evaluation
        $intendedBrand = !empty($intentSpec['brand']) ? trim((string) $intentSpec['brand']) : null;
        $checkoutBrand = !empty($checkoutSpec['brand']) ? trim((string) $checkoutSpec['brand']) : null;

        if ($intendedBrand !== null && $checkoutBrand !== null) {
            if (strcasecmp($intendedBrand, $checkoutBrand) !== 0) {
                $discrepancies[] = new Discrepancy(
                    category:      DiscrepancyCategory::SPECIFICATION,
                    severity:      DiscrepancySeverity::CRITICAL,
                    field:         'brand',
                    intendedValue: $intendedBrand,
                    actualValue:   $checkoutBrand,
                    message:       "Brand mismatch: Expected brand [{$intendedBrand}], received [{$checkoutBrand}].",
                );
            }
        }

        // 3. Model Evaluation
        $intendedModel = !empty($intentSpec['model']) ? trim((string) $intentSpec['model']) : null;
        $checkoutModel = !empty($checkoutSpec['model']) ? trim((string) $checkoutSpec['model']) : null;

        if ($intendedModel !== null && $checkoutModel !== null) {
            if (strcasecmp($intendedModel, $checkoutModel) !== 0) {
                $discrepancies[] = new Discrepancy(
                    category:      DiscrepancyCategory::SPECIFICATION,
                    severity:      DiscrepancySeverity::CRITICAL,
                    field:         'model',
                    intendedValue: $intendedModel,
                    actualValue:   $checkoutModel,
                    message:       "Model deviation: Expected model [{$intendedModel}], received [{$checkoutModel}].",
                );
            }
        }

        // 4. Colour / Finish Variation & Substitution Tolerances
        $intendedColour = !empty($intentSpec['colour']) ? trim((string) $intentSpec['colour']) : null;
        $checkoutColour = !empty($checkoutSpec['colour']) ? trim((string) $checkoutSpec['colour']) : null;

        if ($intendedColour !== null && $checkoutColour !== null) {
            if (strcasecmp($intendedColour, $checkoutColour) !== 0) {
                $isColourTolerated = !empty($allowedSubs['colour_variants']);

                $discrepancies[] = new Discrepancy(
                    category:      DiscrepancyCategory::SUBSTITUTION,
                    severity:      $isColourTolerated ? DiscrepancySeverity::INFO : DiscrepancySeverity::WARNING,
                    field:         'colour',
                    intendedValue: $intendedColour,
                    actualValue:   $checkoutColour,
                    message:       $isColourTolerated
                        ? "Colour variation [{$intendedColour}] -> [{$checkoutColour}] allowed by customer substitution preferences."
                        : "Colour variation detected: Intended [{$intendedColour}], checkout has [{$checkoutColour}] without pre-approval.",
                    isTolerated:   $isColourTolerated,
                );
            }
        }

        // 5. Storage / Capacity Evaluation
        $intendedStorage = !empty($intentSpec['storage']) ? trim((string) $intentSpec['storage']) : null;
        $checkoutStorage = !empty($checkoutSpec['storage']) ? trim((string) $checkoutSpec['storage']) : null;

        if ($intendedStorage !== null && $checkoutStorage !== null) {
            if (strcasecmp($intendedStorage, $checkoutStorage) !== 0) {
                $intendedGb = $this->parseGigabytes($intendedStorage);
                $checkoutGb = $this->parseGigabytes($checkoutStorage);

                if ($intendedGb !== null && $checkoutGb !== null) {
                    if ($checkoutGb < $intendedGb) {
                        // Storage Downgrade: strictly critical
                        $discrepancies[] = new Discrepancy(
                            category:      DiscrepancyCategory::SPECIFICATION,
                            severity:      DiscrepancySeverity::CRITICAL,
                            field:         'storage',
                            intendedValue: $intendedStorage,
                            actualValue:   $checkoutStorage,
                            message:       "Specification downgrade: Storage reduced from [{$intendedStorage}] to [{$checkoutStorage}].",
                        );
                    } elseif ($checkoutGb > $intendedGb) {
                        // Storage Upgrade: check if customer permitted storage upgrades
                        $isUpgradeTolerated = !empty($allowedSubs['storage_upgrade']);

                        $discrepancies[] = new Discrepancy(
                            category:      DiscrepancyCategory::SUBSTITUTION,
                            severity:      $isUpgradeTolerated ? DiscrepancySeverity::INFO : DiscrepancySeverity::WARNING,
                            field:         'storage',
                            intendedValue: $intendedStorage,
                            actualValue:   $checkoutStorage,
                            message:       $isUpgradeTolerated
                                ? "Storage upgrade from [{$intendedStorage}] to [{$checkoutStorage}] permitted by customer substitution rules."
                                : "Storage capacity differs: Intended [{$intendedStorage}], checkout has [{$checkoutStorage}]. Customer confirmation suggested.",
                            isTolerated:   $isUpgradeTolerated,
                        );
                    }
                } else {
                    // String difference without parsable GB
                    $discrepancies[] = new Discrepancy(
                        category:      DiscrepancyCategory::SPECIFICATION,
                        severity:      DiscrepancySeverity::WARNING,
                        field:         'storage',
                        intendedValue: $intendedStorage,
                        actualValue:   $checkoutStorage,
                        message:       "Storage capacity specification differs: [{$intendedStorage}] vs [{$checkoutStorage}].",
                    );
                }
            }
        }

        return $discrepancies;
    }

    /**
     * Parses human-readable storage string (e.g. '128GB', '1TB', '256 gb') to integer gigabytes.
     */
    private function parseGigabytes(string $storage): ?int
    {
        $normalized = strtoupper(trim($storage));

        if (preg_match('/^(\d+)\s*TB$/', $normalized, $matches)) {
            return ((int) $matches[1]) * 1024;
        }

        if (preg_match('/^(\d+)\s*GB$/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(\d+)\s*MB$/', $normalized, $matches)) {
            return (int) round(((int) $matches[1]) / 1024);
        }

        return null;
    }
}
