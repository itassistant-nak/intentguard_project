<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeCategory: string
{
    case ITEM_NOT_AS_DESCRIBED       = 'item_not_as_described';
    case UNAUTHORIZED_SUBSTITUTION   = 'unauthorized_substitution';
    case DELIVERY_NEVER_ARRIVED      = 'delivery_never_arrived';
    case HIDDEN_FEE_CHARGE           = 'hidden_fee_charge';
    case UNRESPONSIVE_MERCHANT       = 'unresponsive_merchant';

    public function label(): string
    {
        return match ($this) {
            self::ITEM_NOT_AS_DESCRIBED     => 'Item Not As Described',
            self::UNAUTHORIZED_SUBSTITUTION => 'Unauthorized Product Substitution',
            self::DELIVERY_NEVER_ARRIVED    => 'Product Never Delivered',
            self::HIDDEN_FEE_CHARGE         => 'Undisclosed Fee or Price Surge Charged',
            self::UNRESPONSIVE_MERCHANT     => 'Merchant Unresponsive to Inquiries',
        };
    }
}
