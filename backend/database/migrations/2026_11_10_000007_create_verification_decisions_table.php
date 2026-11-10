<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the verification_decisions table — records every semantic integrity
     * evaluation performed between a customer's Purchase Intent and the merchant's
     * actual Checkout Payload.
     */
    public function up(): void
    {
        Schema::create('verification_decisions', function (Blueprint $table) {
            $table->id();

            // Associated Purchase Intent
            $table->foreignId('purchase_intent_id')
                ->constrained('purchase_intents')
                ->restrictOnDelete();

            // The merchant's actual checkout payload that was evaluated
            $table->json('checkout_payload_json');

            // Categorized discrepancy flags and detailed audit findings
            $table->json('discrepancy_flags_json');

            // Normalized confidence score (0.0000 to 1.0000)
            $table->decimal('confidence_score', 5, 4)->default(1.0000);

            // Verdict code: MATCH | MISMATCH | UNCERTAIN | REQUIRE_CUSTOMER_CONFIRMATION
            $table->string('verdict', 32)->index();

            // Whether payment capture should be held or blocked
            $table->boolean('hold_payment_capture')->default(false);

            // Exact timestamp when the integrity evaluation completed
            $table->timestamp('decided_at')->useCurrent();

            $table->timestamps();

            // Query indexes
            $table->index(['purchase_intent_id', 'verdict']);
            $table->index('decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_decisions');
    }
};
