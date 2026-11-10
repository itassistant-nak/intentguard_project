<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantAgreementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_active_merchant_agreement(): void
    {
        $agreement = MerchantAgreement::factory()->create([
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/agreements/active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', '1.0.0')
            ->assertJsonPath('data.content_hash', $agreement->content_hash)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'version',
                    'title',
                    'content_hash',
                    'policies' => [
                        'product_accuracy',
                        'shipping_delivery',
                        'substitution',
                        'refund_replacement',
                    ],
                ],
            ]);
    }

    public function test_merchant_can_accept_agreement_with_matching_terms_digest(): void
    {
        $merchant = Merchant::factory()->create();
        $agreement = MerchantAgreement::factory()->create([
            'is_active' => true,
        ]);

        $payload = [
            'agreement_id' => $agreement->id,
            'signatory_name' => 'Alice Cooper',
            'signatory_title' => 'Chief Executive Officer',
            'terms_digest_snapshot' => $agreement->content_hash,
        ];

        $response = $this->postJson("/api/v1/merchants/{$merchant->intentguard_id}/agreements/accept", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signatory_name', 'Alice Cooper')
            ->assertJsonPath('data.terms_digest_snapshot', $agreement->content_hash);

        $this->assertDatabaseHas('merchant_agreement_acceptances', [
            'merchant_id' => $merchant->id,
            'merchant_agreement_id' => $agreement->id,
            'signatory_name' => 'Alice Cooper',
        ]);
    }

    public function test_acceptance_fails_when_terms_digest_is_tampered_or_invalid(): void
    {
        $merchant = Merchant::factory()->create();
        $agreement = MerchantAgreement::factory()->create([
            'is_active' => true,
        ]);

        $tamperedDigest = hash('sha256', 'tampered-unauthorized-policy-text');

        $payload = [
            'agreement_id' => $agreement->id,
            'signatory_name' => 'Alice Cooper',
            'signatory_title' => 'Chief Executive Officer',
            'terms_digest_snapshot' => $tamperedDigest,
        ];

        $response = $this->postJson("/api/v1/merchants/{$merchant->intentguard_id}/agreements/accept", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['terms_digest_snapshot']);

        $this->assertDatabaseMissing('merchant_agreement_acceptances', [
            'merchant_id' => $merchant->id,
            'terms_digest_snapshot' => $tamperedDigest,
        ]);
    }
}
