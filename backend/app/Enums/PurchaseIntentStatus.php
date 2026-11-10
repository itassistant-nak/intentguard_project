<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseIntentStatus: string
{
    /**
     * Intent recorded; awaiting checkout to be submitted for verification.
     */
    case PENDING = 'pending';

    /**
     * Customer has confirmed and submitted the checkout for integrity verification.
     */
    case CONFIRMED = 'confirmed';

    /**
     * Semantic Integrity Engine confirmed MATCH — safe to capture payment.
     */
    case VERIFIED = 'verified';

    /**
     * Engine detected a MISMATCH — payment capture blocked.
     */
    case MISMATCHED = 'mismatched';

    /**
     * Customer or system cancelled the intent before resolution.
     */
    case CANCELLED = 'cancelled';

    /**
     * Intent expired without being confirmed (TTL exceeded).
     */
    case EXPIRED = 'expired';

    /**
     * Returns all terminal (non-active) statuses.
     *
     * @return list<self>
     */
    public static function terminalStatuses(): array
    {
        return [
            self::VERIFIED,
            self::MISMATCHED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    /**
     * Returns the human-readable label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING     => 'Pending',
            self::CONFIRMED   => 'Confirmed',
            self::VERIFIED    => 'Verified',
            self::MISMATCHED  => 'Mismatched',
            self::CANCELLED   => 'Cancelled',
            self::EXPIRED     => 'Expired',
        };
    }

    /**
     * Returns true if the intent is still active (mutable).
     */
    public function isActive(): bool
    {
        return !in_array($this, self::terminalStatuses(), true);
    }
}
