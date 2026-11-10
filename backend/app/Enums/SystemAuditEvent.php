<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemAuditEvent: string
{
    case MERCHANT_REGISTERED          = 'merchant.registered';
    case MERCHANT_STATUS_UPDATED      = 'merchant.status_updated';
    case MERCHANT_AGREEMENT_ACCEPTED  = 'merchant.agreement_accepted';
    case INTENT_RECORDED              = 'intent.recorded';
    case INTENT_CANCELLED             = 'intent.cancelled';
    case VERIFICATION_DECIDED         = 'verification.decided';
    case PAYMENT_ORDER_CREATED        = 'payment.order_created';
    case PAYMENT_AUTHORIZED           = 'payment.authorized';
    case PAYMENT_CAPTURED             = 'payment.captured';
    case PAYMENT_HELD                 = 'payment.held';
    case PAYMENT_VOIDED               = 'payment.voided';
    case DISPUTE_OPENED               = 'dispute.opened';
    case DISPUTE_RESPONDED            = 'dispute.responded';
    case DISPUTE_RESOLVED             = 'dispute.resolved';
    case COMPLIANCE_PENALIZED         = 'compliance.penalized';
    case COMPLIANCE_APPEALED          = 'compliance.appealed';
    case COMPLIANCE_ADJUDICATED       = 'compliance.adjudicated';
    case SECURITY_LOGIN               = 'security.login';
    case SECURITY_FAILED_ATTEMPT      = 'security.failed_attempt';
    case SECURITY_RATE_LIMITED        = 'security.rate_limited';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANT_REGISTERED         => 'Merchant Registered',
            self::MERCHANT_STATUS_UPDATED     => 'Merchant Status Updated',
            self::MERCHANT_AGREEMENT_ACCEPTED => 'Merchant Agreement Accepted',
            self::INTENT_RECORDED             => 'Purchase Intent Recorded',
            self::INTENT_CANCELLED            => 'Purchase Intent Cancelled',
            self::VERIFICATION_DECIDED        => 'Verification Decision Decided',
            self::PAYMENT_ORDER_CREATED       => 'Payment Order Created',
            self::PAYMENT_AUTHORIZED          => 'Payment Authorized',
            self::PAYMENT_CAPTURED            => 'Payment Captured',
            self::PAYMENT_HELD                => 'Payment Capture Held',
            self::PAYMENT_VOIDED              => 'Payment Authorization Voided',
            self::DISPUTE_OPENED              => 'Customer Dispute Opened',
            self::DISPUTE_RESPONDED           => 'Merchant Responded to Dispute',
            self::DISPUTE_RESOLVED            => 'Customer Dispute Resolved',
            self::COMPLIANCE_PENALIZED        => 'Compliance Score Penalized',
            self::COMPLIANCE_APPEALED         => 'Compliance Score Appealed',
            self::COMPLIANCE_ADJUDICATED      => 'Compliance Appeal Adjudicated',
            self::SECURITY_LOGIN              => 'User Logged In',
            self::SECURITY_FAILED_ATTEMPT     => 'Security Authentication Failed',
            self::SECURITY_RATE_LIMITED       => 'Rate Limit Threshold Exceeded',
        };
    }
}
