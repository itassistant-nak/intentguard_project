<?php

declare(strict_types=1);

namespace App\Engines\SemanticIntegrityEngine;

use App\Enums\DiscrepancySeverity;
use App\Enums\VerificationVerdict;
use App\Models\PurchaseIntent;

class SemanticIntegrityEngine
{
    public function __construct(
        private readonly DeterministicEvaluator $deterministicEvaluator = new DeterministicEvaluator(),
        private readonly SemanticAttributeEvaluator $semanticEvaluator = new SemanticAttributeEvaluator(),
    ) {}

    /**
     * Executes the hybrid verification engine against an intent and checkout payload.
     *
     * @param  PurchaseIntent       $intent
     * @param  array<string, mixed> $checkoutPayload
     * @return IntegrityReport
     */
    public function verify(PurchaseIntent $intent, array $checkoutPayload): IntegrityReport
    {
        // 1. Deterministic Rule Layer
        $deterministicDiscrepancies = $this->deterministicEvaluator->evaluate($intent, $checkoutPayload);

        // 2. Semantic Attribute Layer
        $semanticDiscrepancies = $this->semanticEvaluator->evaluate($intent, $checkoutPayload);

        // 3. Merge All Findings
        $allDiscrepancies = array_merge($deterministicDiscrepancies, $semanticDiscrepancies);

        // 4. Calculate Confidence Score
        $confidenceScore = $this->calculateConfidenceScore($allDiscrepancies);

        // 5. Determine Verdict & Payment Hold
        $verdict = $this->determineVerdict($allDiscrepancies, $confidenceScore);
        $holdPaymentCapture = $verdict->shouldHoldCapture();

        return new IntegrityReport(
            confidenceScore:    $confidenceScore,
            verdict:            $verdict,
            discrepancies:      $allDiscrepancies,
            holdPaymentCapture: $holdPaymentCapture,
        );
    }

    /**
     * Compute a normalized confidence score (0.0000 to 1.0000).
     *
     * @param list<Discrepancy> $discrepancies
     */
    private function calculateConfidenceScore(array $discrepancies): float
    {
        $hasCriticalNonTolerated = false;
        $score = 1.0;

        foreach ($discrepancies as $discrepancy) {
            if ($discrepancy->isTolerated) {
                // Info / pre-approved substitution: no penalty
                continue;
            }

            if ($discrepancy->severity === DiscrepancySeverity::CRITICAL) {
                $hasCriticalNonTolerated = true;
                $score -= 0.50;
            } elseif ($discrepancy->severity === DiscrepancySeverity::WARNING) {
                $score -= 0.15;
            }
        }

        if ($hasCriticalNonTolerated) {
            return 0.0;
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * Map discrepancies and confidence score to a definitive VerificationVerdict.
     *
     * @param list<Discrepancy> $discrepancies
     */
    private function determineVerdict(array $discrepancies, float $confidenceScore): VerificationVerdict
    {
        // Any critical non-tolerated failure results in immediate MISMATCH
        foreach ($discrepancies as $discrepancy) {
            if ($discrepancy->severity === DiscrepancySeverity::CRITICAL && !$discrepancy->isTolerated) {
                return VerificationVerdict::MISMATCH;
            }
        }

        // Warnings check
        $hasUntoleratedWarnings = false;
        foreach ($discrepancies as $discrepancy) {
            if ($discrepancy->severity === DiscrepancySeverity::WARNING && !$discrepancy->isTolerated) {
                $hasUntoleratedWarnings = true;
                break;
            }
        }

        if ($hasUntoleratedWarnings) {
            return ($confidenceScore >= 0.70)
                ? VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION
                : VerificationVerdict::UNCERTAIN;
        }

        return VerificationVerdict::MATCH;
    }
}
