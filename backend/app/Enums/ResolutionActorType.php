<?php

declare(strict_types=1);

namespace App\Enums;

enum ResolutionActorType: string
{
    case CUSTOMER = 'customer';
    case SYSTEM   = 'system';
    case ADMIN    = 'admin';
    case MERCHANT = 'merchant';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::SYSTEM   => 'Automated System',
            self::ADMIN    => 'Administrator',
            self::MERCHANT => 'Merchant Store',
        };
    }
}
