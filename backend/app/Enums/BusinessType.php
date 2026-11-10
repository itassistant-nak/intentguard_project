<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessType: string
{
    case SOLE_PROPRIETORSHIP = 'sole_proprietorship';
    case CORPORATION = 'corporation';
    case LLC = 'llc';
    case PARTNERSHIP = 'partnership';

    /**
     * Get all raw values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
