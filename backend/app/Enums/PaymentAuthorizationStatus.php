<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentAuthorizationStatus: string
{
    case CREATED = 'created';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case VOIDED = 'voided';
    case HELD = 'held';

    public function label(): string
    {
        return match ($this) {
            self::CREATED    => 'Order Created',
            self::AUTHORIZED => 'Payment Authorized',
            self::CAPTURED   => 'Payment Captured',
            self::VOIDED     => 'Authorization Voided',
            self::HELD       => 'Capture Held Pending Confirmation',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::CAPTURED, self::VOIDED => true,
            default                      => false,
        };
    }

    public function canCapture(): bool
    {
        return match ($this) {
            self::AUTHORIZED, self::HELD => true,
            default                      => false,
        };
    }

    public function canVoid(): bool
    {
        return match ($this) {
            self::AUTHORIZED, self::HELD => true,
            default                      => false,
        };
    }
}
