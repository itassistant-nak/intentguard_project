<?php

declare(strict_types=1);

namespace App\Engines\SemanticIntegrityEngine;

use App\Enums\DiscrepancyCategory;
use App\Enums\DiscrepancySeverity;

readonly class Discrepancy
{
    public function __construct(
        public DiscrepancyCategory $category,
        public DiscrepancySeverity $severity,
        public string $field,
        public mixed $intendedValue,
        public mixed $actualValue,
        public string $message,
        public bool $isTolerated = false,
    ) {}

    /**
     * Convert discrepancy to an associative array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category'       => $this->category->value,
            'category_label' => $this->category->label(),
            'severity'       => $this->severity->value,
            'severity_label' => $this->severity->label(),
            'field'          => $this->field,
            'intended_value' => $this->intendedValue,
            'actual_value'   => $this->actualValue,
            'message'        => $this->message,
            'is_tolerated'   => $this->isTolerated,
        ];
    }
}
