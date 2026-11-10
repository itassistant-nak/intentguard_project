<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Models\Merchant;
use App\Models\PurchaseEvidence;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Services\EvidenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function makeIntent(User $customer): PurchaseIntent
    {
        $merchant = Merchant::factory()->create();

        return PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Record Evidence
    // -------------------------------------------------------------------------

    public function test_customer_can_record_inline_evidence_for_intent(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $payload = [
            'evidence_type' => 'product_page',
            'source_url' => 'https://example.com/products/iphone-15',
            'content' => '<html><head><title>iPhone 15 - $999</title></head><body>Product description</body></html>',
            'metadata' => [
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'viewport' => '1920x1080',
            ],
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/evidence",
            $payload
        );

        $expectedHash = hash('sha256', $payload['content']);

        $response->assertStatus(201)
            ->assertJsonPath('purchase_intent_id', $intent->id)
            ->assertJsonPath('evidence_type', 'product_page')
            ->assertJsonPath('evidence_type_label', 'Product Page Snapshot')
            ->assertJsonPath('source_url', 'https://example.com/products/iphone-15')
            ->assertJsonPath('has_inline_snapshot', true)
            ->assertJsonPath('has_blob_snapshot', false)
            ->assertJsonPath('content_hash', $expectedHash)
            ->assertJsonPath('metadata.viewport', '1920x1080');

        $this->assertDatabaseHas('purchase_evidence', [
            'purchase_intent_id' => $intent->id,
            'evidence_type' => EvidenceType::PRODUCT_PAGE->value,
            'content_hash' => $expectedHash,
            'submitted_by' => $customer->id,
        ]);
    }

    public function test_customer_can_record_blob_reference_evidence(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);
        $blobHash = hash('sha256', 'large_screenshot_blob_data');

        $payload = [
            'evidence_type' => 'checkout_screen',
            'source_url' => 'https://example.com/checkout',
            'blob_path' => 'evidence/blobs/checkout_screenshot.png',
            'content_hash' => $blobHash,
        ];

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/evidence",
            $payload
        );

        $response->assertStatus(201)
            ->assertJsonPath('evidence_type', 'checkout_screen')
            ->assertJsonPath('has_inline_snapshot', false)
            ->assertJsonPath('has_blob_snapshot', true)
            ->assertJsonPath('content_snapshot_blob_path', 'evidence/blobs/checkout_screenshot.png')
            ->assertJsonPath('content_hash', $blobHash);

        $this->assertDatabaseHas('purchase_evidence', [
            'purchase_intent_id' => $intent->id,
            'evidence_type' => EvidenceType::CHECKOUT_SCREEN->value,
            'content_snapshot_blob_path' => 'evidence/blobs/checkout_screenshot.png',
            'content_hash' => $blobHash,
        ]);
    }

    public function test_recording_fails_when_neither_content_nor_blob_path_provided(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/evidence",
            [
                'evidence_type' => 'receipt',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content', 'blob_path']);
    }

    public function test_recording_fails_for_invalid_evidence_type(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $response = $this->actingAs($customer)->postJson(
            "/api/v1/intents/{$intent->id}/evidence",
            [
                'evidence_type' => 'invalid_type',
                'content' => 'Some content',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidence_type']);
    }

    public function test_customer_cannot_record_evidence_for_another_customers_intent(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $intent = $this->makeIntent($owner);

        $response = $this->actingAs($stranger)->postJson(
            "/api/v1/intents/{$intent->id}/evidence",
            [
                'evidence_type' => 'product_page',
                'content' => 'Sample content',
            ]
        );

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INTENT_NOT_FOUND');
    }

    // -------------------------------------------------------------------------
    // Query & Filtering
    // -------------------------------------------------------------------------

    public function test_can_list_evidence_for_intent(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        PurchaseEvidence::factory()->productPage()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);
        PurchaseEvidence::factory()->paymentQuote()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/intents/{$intent->id}/evidence"
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_evidence_by_type(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        PurchaseEvidence::factory()->productPage()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);
        PurchaseEvidence::factory()->paymentQuote()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/intents/{$intent->id}/evidence?type=payment_quote"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.evidence_type', 'payment_quote');
    }

    public function test_can_show_single_evidence_record_including_raw_content(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $evidence = PurchaseEvidence::factory()->productPage()->create([
            'purchase_intent_id' => $intent->id,
            'content_snapshot' => '<h1>Raw Product Spec Snapshot</h1>',
            'content_hash' => hash('sha256', '<h1>Raw Product Spec Snapshot</h1>'),
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson("/api/v1/evidence/{$evidence->id}");

        $response->assertOk()
            ->assertJsonPath('id', $evidence->id)
            ->assertJsonPath('content_snapshot', '<h1>Raw Product Spec Snapshot</h1>');
    }

    // -------------------------------------------------------------------------
    // Provenance Chain
    // -------------------------------------------------------------------------

    public function test_can_retrieve_provenance_chain_for_intent(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        PurchaseEvidence::factory()->productPage()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);
        PurchaseEvidence::factory()->checkoutScreen()->create([
            'purchase_intent_id' => $intent->id,
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/intents/{$intent->id}/evidence/provenance-chain"
        );

        $response->assertOk()
            ->assertJsonPath('intent_id', $intent->id)
            ->assertJsonPath('total_records', 2)
            ->assertJsonPath('integrity_summary.total', 2)
            ->assertJsonPath('integrity_summary.intact', 2)
            ->assertJsonPath('integrity_summary.tampered', 0)
            ->assertJsonStructure([
                'intent_id',
                'total_records',
                'chain' => [
                    'product_page',
                    'checkout_screen',
                ],
                'integrity_summary' => [
                    'total',
                    'intact',
                    'tampered',
                    'unverifiable',
                ],
            ]);
    }

    // -------------------------------------------------------------------------
    // Cryptographic Tamper Verification
    // -------------------------------------------------------------------------

    public function test_evidence_integrity_verification_passes_for_intact_record(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $content = 'Genuine untampered checkout screen';
        $evidence = PurchaseEvidence::factory()->create([
            'purchase_intent_id' => $intent->id,
            'content_snapshot' => $content,
            'content_hash' => hash('sha256', $content),
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/evidence/{$evidence->id}/verify-integrity"
        );

        $response->assertOk()
            ->assertJsonPath('integrity_ok', true);
    }

    public function test_evidence_integrity_verification_fails_when_tampered(): void
    {
        $customer = $this->makeCustomer();
        $intent = $this->makeIntent($customer);

        $evidence = PurchaseEvidence::factory()->create([
            'purchase_intent_id' => $intent->id,
            'content_snapshot' => 'Tampered content that does not match original digest',
            'content_hash' => hash('sha256', 'original_authentic_content'),
            'submitted_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->getJson(
            "/api/v1/evidence/{$evidence->id}/verify-integrity"
        );

        $response->assertOk()
            ->assertJsonPath('integrity_ok', false);
    }

    public function test_unauthenticated_evidence_requests_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/intents/1/evidence', []);

        $response->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
