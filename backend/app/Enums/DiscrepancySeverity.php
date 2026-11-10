<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscrepancySeverity: string
{
    /**
     * Critical failure: immediate integrity violation, causes MISMATCH verdict.
     */
    case CRITICAL = 'critical';

    /**
     * Warning: ambiguous or non-trivial variation that requires customer review or causes UNCERTAIN verdict.
     */
    case WARNING = 'warning';

    /**
     * Informational: permitted variation, acceptable customer substitution, or favorable adjustment.
     */
    case INFO = 'info';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::WARNING  => 'Warning',
            self::INFO     => 'Informational',
        };
    }
}
