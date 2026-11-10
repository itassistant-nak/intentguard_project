<?php

declare(strict_types=1);

namespace App\Enums;

enum CaptureAction: string
{
    /**
     * Intent matches checkout; execute payment capture immediately.
     */
    case CAPTURE_IMMEDIATE = 'capture_immediate';

    /**
     * Ambiguous or customer confirmation required; hold capture until resolved.
     */
    case CAPTURE_HOLD = 'capture_hold';

    /**
     * Critical mismatch or customer rejection; void/cancel payment capture.
     */
    case CAPTURE_VOID = 'capture_void';

    public function label(): string
    {
        return match ($this) {
            self::CAPTURE_IMMEDIATE => 'Capture Immediate',
            self::CAPTURE_HOLD      => 'Hold Payment Capture',
            self::CAPTURE_VOID      => 'Void Payment Capture',
        };
    }
}
