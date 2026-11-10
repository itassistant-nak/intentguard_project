<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeStatus: string
{
    case OPENED                  = 'opened';
    case UNDER_MERCHANT_RESPONSE = 'under_merchant_response';
    case ESCALATED_TO_ADMIN      = 'escalated_to_admin';
    case RESOLVED_REFUNDED       = 'resolved_refunded';
    case RESOLVED_REPLACED       = 'resolved_replaced';
    case RESOLVED_REJECTED       = 'resolved_rejected';

    public function label(): string
    {
        return match ($this) {
            self::OPENED                  => 'Dispute Opened',
            self::UNDER_MERCHANT_RESPONSE => 'Awaiting Merchant Response',
            self::ESCALATED_TO_ADMIN      => 'Escalated to IntentGuard Adjudication',
            self::RESOLVED_REFUNDED       => 'Resolved: Full Refund Issued',
            self::RESOLVED_REPLACED       => 'Resolved: Correct Item Replacement',
            self::RESOLVED_REJECTED       => 'Resolved: Dispute Dismissed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::RESOLVED_REFUNDED,
            self::RESOLVED_REPLACED,
            self::RESOLVED_REJECTED,
        ], true);
    }
}
