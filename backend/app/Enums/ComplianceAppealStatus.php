<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplianceAppealStatus: string
{
    case NONE           = 'none';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED       = 'approved';
    case REJECTED       = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NONE           => 'No Appeal Filed',
            self::PENDING_REVIEW => 'Appeal Under Administrative Review',
            self::APPROVED       => 'Appeal Approved (Penalty Reversed)',
            self::REJECTED       => 'Appeal Rejected (Penalty Sustained)',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING_REVIEW;
    }
}
