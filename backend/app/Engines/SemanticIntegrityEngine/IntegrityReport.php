<?php

declare(strict_types=1);

namespace App\Engines\SemanticIntegrityEngine;

use App\Enums\DiscrepancySeverity;
use App\Enums\VerificationVerdict;

readonly class IntegrityReport
{
    /**
     * @param list<Discrepancy> $discrepancies
     */
    public function __construct(
        public float $confidenceScore,
        public VerificationVerdict $verdict,
        public array $discrepancies,
        public bool $holdPaymentCapture,
    ) {}

    /**
     * Check if report has any critical discrepancies.
     */
    public function hasCriticalDiscrepancies(): bool
    {
        foreach ($this->discrepancies as $discrepancy) {
            if ($discrepancy->severity === DiscrepancySeverity::CRITICAL && !$discrepancy->isTolerated) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if report has any non-critical warnings.
     */
    public function hasWarnings(): bool
    {
        foreach ($this->discrepancies as $discrepancy) {
            if ($discrepancy->severity === DiscrepancySeverity::WARNING && !$discrepancy->isTolerated) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert report to array representation for persistence and API resources.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'confidence_score'     => round($this->confidenceScore, 4),
            'verdict'              => $this->verdict->value,
            'verdict_label'        => $this->verdict->label(),
            'hold_payment_capture' => $this->holdPaymentCapture,
            'discrepancy_count'    => count($this->discrepancies),
            'discrepancies'        => array_map(fn (Discrepancy $d) => $d->toArray(), $this->discrepancies),
        ];
    }
}
