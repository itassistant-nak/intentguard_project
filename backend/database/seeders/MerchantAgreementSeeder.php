<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MerchantAgreement;
use Illuminate\Database\Seeder;

class MerchantAgreementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accuracy = <<<'POLICY'
Merchants registered on IntentGuard agree and covenant that every item fulfilled shall strictly conform in identity, brand, model, SKU, memory/storage, physical condition, and included accessories to the customer's verified purchase intent. Unilateral alteration of product specifications between checkout and dispatch is strictly prohibited.
POLICY;

        $shipping = <<<'POLICY'
Merchants agree to dispatch orders within the promised SLA timeframe (maximum 48 business hours unless otherwise specified in the checkout agreement) and provide an authentic, scannable carrier tracking number. Hidden shipping surcharges are deemed a breach of agreement.
POLICY;

        $substitutions = <<<'POLICY'
No product substitution, refurbishment alternative, or variant swap may be dispatched unless the customer has provided prior verified digital approval via IntentGuard.
POLICY;

        $refund = <<<'POLICY'
If an intent mismatch or unapproved substitution is verified by IntentGuard, the merchant agrees to provide a full refund or express replacement within 3 business days of verification, without requiring return shipping costs from the consumer.
POLICY;

        $digest = MerchantAgreement::computeDigest($accuracy, $shipping, $substitutions, $refund);

        MerchantAgreement::firstOrCreate(
            ['version' => '1.0.0'],
            [
                'title' => 'IntentGuard Standard Merchant Fulfilment & Purchase Integrity Agreement',
                'content_hash' => $digest,
                'product_accuracy_policy' => $accuracy,
                'shipping_delivery_policy' => $shipping,
                'substitution_policy' => $substitutions,
                'refund_replacement_policy' => $refund,
                'is_active' => true,
                'published_at' => now(),
            ],
        );
    }
}
