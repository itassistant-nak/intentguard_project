<?php

declare(strict_types=1);

namespace App\Enums;

enum ResolutionAuditAction: string
{
    case HOLD_PLACED         = 'hold_placed';
    case CUSTOMER_CONFIRMED  = 'customer_confirmed';
    case CUSTOMER_REJECTED   = 'customer_rejected';
    case CAPTURE_RELEASED    = 'capture_released';
    case CAPTURE_VOIDED      = 'capture_voided';
    case HOLD_EXPIRED        = 'hold_expired';

    public function label(): string
    {
        return match ($this) {
            self::HOLD_PLACED         => 'Payment Capture Hold Placed',
            self::CUSTOMER_CONFIRMED  => 'Customer Confirmed Variance',
            self::CUSTOMER_REJECTED   => 'Customer Rejected Variance',
            self::CAPTURE_RELEASED    => 'Payment Capture Released',
            self::CAPTURE_VOIDED      => 'Payment Capture Voided',
            self::HOLD_EXPIRED        => 'Payment Authorization Hold Expired',
        };
    }
}
