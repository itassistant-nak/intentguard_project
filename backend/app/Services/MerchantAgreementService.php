<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantAgreement;
use App\Models\MerchantAgreementAcceptance;
use Illuminate\Validation\ValidationException;

class MerchantAgreementService
{
    /**
     * Get the current active merchant agreement.
     */
    public function getActiveAgreement(): ?MerchantAgreement
    {
        return MerchantAgreement::active()->first();
    }

    /**
     * Record merchant digital acceptance of an agreement with cryptographic validation.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function acceptAgreement(Merchant $merchant, array $data): MerchantAgreementAcceptance
    {
        $agreement = MerchantAgreement::findOrFail($data['agreement_id']);

        if (!$agreement->is_active) {
            throw ValidationException::withMessages([
                'agreement_id' => ['The specified agreement is no longer active for new signatures.'],
            ]);
        }

        // Cryptographic check: submitted terms digest must match server's agreement content hash
        if (!hash_equals($agreement->content_hash, (string) $data['terms_digest_snapshot'])) {
            throw ValidationException::withMessages([
                'terms_digest_snapshot' => [
                    'Cryptographic mismatch: the submitted terms digest does not match the agreement content hash. Agreement terms may have changed.',
                ],
            ]);
        }

        // Idempotency: check if merchant already accepted this agreement version
        $existing = MerchantAgreementAcceptance::where('merchant_id', $merchant->id)
            ->where('merchant_agreement_id', $agreement->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return MerchantAgreementAcceptance::create([
            'merchant_id' => $merchant->id,
            'merchant_agreement_id' => $agreement->id,
            'signatory_name' => (string) $data['signatory_name'],
            'signatory_title' => (string) $data['signatory_title'],
            'ip_address' => (string) ($data['ip_address'] ?? '127.0.0.1'),
            'user_agent' => (string) ($data['user_agent'] ?? 'IntentGuard-Client/1.0'),
            'terms_digest_snapshot' => $agreement->content_hash,
            'accepted_at' => now(),
        ]);
    }
}
