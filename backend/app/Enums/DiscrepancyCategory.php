<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscrepancyCategory: string
{
    case MERCHANT      = 'merchant';
    case PRICE         = 'price';
    case CURRENCY      = 'currency';
    case QUANTITY      = 'quantity';
    case CONDITION     = 'condition';
    case SPECIFICATION = 'specification';
    case SHIPPING      = 'shipping';
    case SUBSTITUTION  = 'substitution';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANT      => 'Merchant Identity',
            self::PRICE         => 'Price & Amount',
            self::CURRENCY      => 'Currency Code',
            self::QUANTITY      => 'Item Quantity',
            self::CONDITION     => 'Product Condition',
            self::SPECIFICATION => 'Product Specification',
            self::SHIPPING      => 'Shipping & Delivery',
            self::SUBSTITUTION  => 'Item Substitution',
        };
    }
}
