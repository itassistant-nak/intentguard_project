<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationVerdict: string
{
    /**
     * Intent strictly matches checkout within all exact and approved tolerances.
     * Safe to capture payment immediately.
     */
    case MATCH = 'MATCH';

    /**
     * Critical attribute violation detected (unauthorized price increase, condition mismatch, unapproved fees).
     * Void or block payment capture.
     */
    case MISMATCH = 'MISMATCH';

    /**
     * Minor or ambiguous specification variation not covered by explicit policy.
     * Payment capture held awaiting resolution.
     */
    case UNCERTAIN = 'UNCERTAIN';

    /**
     * Permitted or borderline substitution requiring explicit customer confirmation.
     * Capture held until customer provides 1-click confirmation or cancellation.
     */
    case REQUIRE_CUSTOMER_CONFIRMATION = 'REQUIRE_CUSTOMER_CONFIRMATION';

    /**
     * Returns human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::MATCH                          => 'Match (Capture Approved)',
            self::MISMATCH                       => 'Mismatch (Capture Blocked)',
            self::UNCERTAIN                      => 'Uncertain (Capture Held)',
            self::REQUIRE_CUSTOMER_CONFIRMATION  => 'Confirmation Required',
        };
    }

    /**
     * Determines if payment capture should be held or blocked.
     */
    public function shouldHoldCapture(): bool
    {
        return match ($this) {
            self::MATCH                          => false,
            self::MISMATCH                       => true,
            self::UNCERTAIN                      => true,
            self::REQUIRE_CUSTOMER_CONFIRMATION  => true,
        };
    }
}
