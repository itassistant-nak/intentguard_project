<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@intentguard.internal'],
            [
                'name' => 'IntentGuard System Administrator',
                'password' => bcrypt('IntentGuardAdminPass!2026'),
                'role' => 'admin',
            ],
        );

        User::firstOrCreate(
            ['email' => 'customer@intentguard.internal'],
            [
                'name' => 'Alex Customer',
                'password' => bcrypt('CustomerPass!2026'),
                'role' => 'customer',
            ],
        );

        User::firstOrCreate(
            ['email' => 'merchant@apexelectronics.co.nz'],
            [
                'name' => 'Sarah Jenkins (Apex Electronics)',
                'password' => bcrypt('MerchantPass!2026'),
                'role' => 'merchant',
            ],
        );

        $this->call([
            MerchantAgreementSeeder::class,
        ]);

        $agreement = \App\Models\MerchantAgreement::active()->first();

        $merchant = \App\Models\Merchant::firstOrCreate(
            ['intentguard_id' => 'IG-MCH-APEX0001'],
            [
                'business_name' => 'Apex Electronics Ltd',
                'legal_name' => 'Apex Consumer Electronics New Zealand Ltd',
                'business_registration_number' => 'NZBN-9429031234567',
                'tax_id' => 'GST-123-456-789',
                'business_type' => \App\Enums\BusinessType::CORPORATION,
                'contact_name' => 'Sarah Jenkins',
                'contact_email' => 'compliance@apexelectronics.co.nz',
                'contact_phone' => '+64 9 123 4567',
                'website_url' => 'https://apexelectronics.co.nz',
                'platform_type' => \App\Enums\MerchantPlatformType::SHOPIFY,
                'status' => \App\Enums\MerchantStatus::VERIFIED,
                'compliance_score' => 96.50,
                'verification_notes' => 'Verified on-site business registry and confirmed integration.',
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ],
        );

        if ($agreement !== null) {
            \App\Models\MerchantAgreementAcceptance::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'merchant_agreement_id' => $agreement->id,
                ],
                [
                    'signatory_name' => 'Sarah Jenkins',
                    'signatory_title' => 'Managing Director',
                    'terms_digest_snapshot' => $agreement->content_hash,
                    'accepted_at' => now(),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'IntentGuard Seeder Agent',
                ],
            );
        }
    }
}
