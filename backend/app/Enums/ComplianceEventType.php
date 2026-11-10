<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplianceEventType: string
{
    case MISMATCH_CONFIRMED            = 'mismatch_confirmed';
    case UNAUTHORIZED_SUBSTITUTION     = 'unauthorized_substitution';
    case DELIVERY_DELAYED              = 'delivery_delayed';
    case CHARGEBACK_RECEIVED           = 'chargeback_received';
    case DISPUTE_FILED                 = 'dispute_filed';
    case MANUAL_DEDUCTION              = 'manual_deduction';
    case POSITIVE_FULFILMENT_RECOVERY  = 'positive_fulfilment_recovery';

    public function defaultScoreDelta(): float
    {
        return match ($this) {
            self::MISMATCH_CONFIRMED           => -15.00,
            self::UNAUTHORIZED_SUBSTITUTION    => -10.00,
            self::DELIVERY_DELAYED             => -5.00,
            self::CHARGEBACK_RECEIVED          => -20.00,
            self::DISPUTE_FILED                => -5.00,
            self::MANUAL_DEDUCTION             => -10.00,
            self::POSITIVE_FULFILMENT_RECOVERY => 2.00,
        };
    }

    public function defaultSeverity(): string
    {
        return match ($this) {
            self::CHARGEBACK_RECEIVED          => 'critical',
            self::MISMATCH_CONFIRMED           => 'major',
            self::UNAUTHORIZED_SUBSTITUTION    => 'major',
            self::DELIVERY_DELAYED             => 'minor',
            self::DISPUTE_FILED                => 'minor',
            self::MANUAL_DEDUCTION             => 'major',
            self::POSITIVE_FULFILMENT_RECOVERY => 'positive',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MISMATCH_CONFIRMED           => 'Confirmed Checkout Mismatch',
            self::UNAUTHORIZED_SUBSTITUTION    => 'Unauthorized Product Substitution',
            self::DELIVERY_DELAYED             => 'Fulfilment / Delivery Delay',
            self::CHARGEBACK_RECEIVED          => 'Payment Chargeback Incurred',
            self::DISPUTE_FILED                => 'Customer Dispute Filed',
            self::MANUAL_DEDUCTION             => 'Administrative Compliance Penalty',
            self::POSITIVE_FULFILMENT_RECOVERY => 'Positive Fulfilment Score Recovery',
        };
    }
}
