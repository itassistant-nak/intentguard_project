<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantPlatformType: string
{
    case SHOPIFY = 'shopify';
    case WOOCOMMERCE = 'woocommerce';
    case MAGENTO = 'magento';
    case CUSTOM = 'custom';
    case PAYPAL_DIRECT = 'paypal_direct';

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
