<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantStatus: string
{
    case PENDING_REVIEW = 'pending_review';
    case UNDER_REVIEW = 'under_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    /**
     * Get all raw values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status allows active transaction verification.
     */
    public function isOperable(): bool
    {
        return $this === self::VERIFIED;
    }
}
