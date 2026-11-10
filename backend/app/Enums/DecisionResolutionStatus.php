<?php

declare(strict_types=1);

namespace App\Enums;

enum DecisionResolutionStatus: string
{
    /**
     * Decision has not been acted upon or is awaiting customer confirmation.
     */
    case UNRESOLVED = 'unresolved';

    /**
     * Customer explicitly accepted the variation or substitution.
     */
    case CUSTOMER_CONFIRMED = 'customer_confirmed';

    /**
     * Customer explicitly rejected the variation or substitution.
     */
    case CUSTOMER_REJECTED = 'customer_rejected';

    /**
     * Hold expired without customer action within gateway window.
     */
    case HOLD_EXPIRED = 'hold_expired';

    public function label(): string
    {
        return match ($this) {
            self::UNRESOLVED         => 'Pending Resolution',
            self::CUSTOMER_CONFIRMED => 'Customer Confirmed',
            self::CUSTOMER_REJECTED  => 'Customer Rejected',
            self::HOLD_EXPIRED       => 'Hold Expired',
        };
    }

    public function isResolved(): bool
    {
        return $this !== self::UNRESOLVED;
    }
}
