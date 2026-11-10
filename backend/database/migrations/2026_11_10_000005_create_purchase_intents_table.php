<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the purchase_intents table — the immutable core record of what a customer
     * intends to purchase before checkout is finalised.
     */
    public function up(): void
    {
        Schema::create('purchase_intents', function (Blueprint $table) {
            $table->id();

            // Parties
            $table->foreignId('customer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->restrictOnDelete();

            // Product identity
            $table->string('product_name', 255);
            $table->string('product_sku', 128)->nullable();
            $table->string('product_url', 2048)->nullable();

            // Detailed specification snapshot (brand, model, colour, storage, condition, etc.)
            $table->json('specification_json');

            // Quantity & pricing
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_cents');     // Store in minor currency units (pence/cents)
            $table->char('currency', 3)->default('USD');       // ISO 4217

            // Shipping terms the customer agreed to at intent time
            $table->json('shipping_conditions_json');

            // Customer-approved substitution boundaries (nullable — strict by default)
            $table->json('allowed_substitutions_json')->nullable();

            // Cryptographic snapshot of the entire intent payload
            $table->char('intent_hash', 64)->unique();          // SHA-256

            // Lifecycle
            $table->string('status', 32)->default('pending')->index();
            // Statuses: pending | confirmed | verified | mismatched | cancelled | expired

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes for common lookups
            $table->index(['customer_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index('intent_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_intents');
    }
};
