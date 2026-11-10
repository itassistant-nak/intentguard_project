<?php

declare(strict_types=1);

namespace App\Enums;

enum EvidenceType: string
{
    /**
     * Snapshot of the merchant's product listing page at the moment the intent was recorded.
     * Captures: product title, description, images, listed price, specifications, seller name.
     */
    case PRODUCT_PAGE = 'product_page';

    /**
     * Snapshot of the checkout summary screen the customer reviewed and accepted.
     * Captures: line items, quantities, subtotal, shipping, taxes, total, merchant identity.
     */
    case CHECKOUT_SCREEN = 'checkout_screen';

    /**
     * The exact payment breakdown/quote presented before authorisation.
     * Captures: authorised amount, currency, fees, any applied discounts.
     */
    case PAYMENT_QUOTE = 'payment_quote';

    /**
     * Post-capture order confirmation or payment receipt.
     * Captures: order ID, final charged amount, fulfilment details, merchant acknowledgement.
     */
    case RECEIPT = 'receipt';

    /**
     * Returns the human-readable label for this evidence type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRODUCT_PAGE    => 'Product Page Snapshot',
            self::CHECKOUT_SCREEN => 'Checkout Screen Snapshot',
            self::PAYMENT_QUOTE   => 'Payment Quote',
            self::RECEIPT         => 'Order Receipt',
        };
    }

    /**
     * Returns the description of what this evidence type captures.
     */
    public function description(): string
    {
        return match ($this) {
            self::PRODUCT_PAGE    => 'Cryptographic snapshot of the merchant product listing at intent time.',
            self::CHECKOUT_SCREEN => 'Cryptographic snapshot of the checkout summary reviewed by the customer.',
            self::PAYMENT_QUOTE   => 'Exact payment breakdown quoted before payment authorisation.',
            self::RECEIPT         => 'Post-capture order confirmation and payment receipt.',
        };
    }

    /**
     * Returns all valid evidence type values as strings.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
