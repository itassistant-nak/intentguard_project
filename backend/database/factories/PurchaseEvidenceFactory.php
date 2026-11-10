<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EvidenceType;
use App\Models\PurchaseEvidence;
use App\Models\PurchaseIntent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseEvidence>
 */
class PurchaseEvidenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PurchaseEvidence>
     */
    protected $model = PurchaseEvidence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawContent = json_encode([
            'title' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'timestamp' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        return [
            'purchase_intent_id' => PurchaseIntent::factory(),
            'evidence_type' => EvidenceType::PRODUCT_PAGE,
            'source_url' => $this->faker->url(),
            'content_snapshot' => $rawContent,
            'content_snapshot_blob_path' => null,
            'content_hash' => hash('sha256', $rawContent),
            'metadata_json' => [
                'user_agent' => $this->faker->userAgent(),
                'viewport' => '1920x1080',
                'locale' => 'en-US',
            ],
            'submitted_by' => User::factory(),
            'captured_at' => now(),
        ];
    }

    /**
     * State: Product page evidence.
     */
    public function productPage(): static
    {
        return $this->state(['evidence_type' => EvidenceType::PRODUCT_PAGE]);
    }

    /**
     * State: Checkout screen evidence.
     */
    public function checkoutScreen(): static
    {
        return $this->state(['evidence_type' => EvidenceType::CHECKOUT_SCREEN]);
    }

    /**
     * State: Payment quote evidence.
     */
    public function paymentQuote(): static
    {
        return $this->state(['evidence_type' => EvidenceType::PAYMENT_QUOTE]);
    }

    /**
     * State: Receipt evidence.
     */
    public function receipt(): static
    {
        return $this->state(['evidence_type' => EvidenceType::RECEIPT]);
    }

    /**
     * State: Blob reference evidence (large file stored externally).
     */
    public function blobReference(string $blobPath = 'snapshots/checkout_full.png'): static
    {
        $mockHash = hash('sha256', 'mock_blob_content_' . $this->faker->uuid());

        return $this->state([
            'content_snapshot' => null,
            'content_snapshot_blob_path' => $blobPath,
            'content_hash' => $mockHash,
        ]);
    }
}
