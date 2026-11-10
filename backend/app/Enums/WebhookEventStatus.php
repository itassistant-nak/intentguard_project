<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookEventStatus: string
{
    case RECEIVED = 'received';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case IGNORED = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED  => 'Received',
            self::PROCESSED => 'Processed Successfully',
            self::FAILED    => 'Processing Failed',
            self::IGNORED   => 'Ignored / Deduplicated',
        };
    }
}
