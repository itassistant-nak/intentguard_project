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
     * Creates customer_disputes table — allows customers to formally file disputes
     * regarding deviations, non-fulfilment, or unauthorized substitutions, and tracks
     * the merchant response and administrative resolution workflow.
     */
    public function up(): void
    {
        Schema::create('customer_disputes', function (Blueprint $table) {
            $table->id();

            // Linkage to Purchase Intent
            $table->foreignId('purchase_intent_id')
                ->constrained('purchase_intents')
                ->restrictOnDelete();

            // Customer filing the dispute
            $table->foreignId('customer_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Merchant being disputed
            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->restrictOnDelete();

            // Category of dispute
            $table->string('dispute_category', 32)->index();

            // Customer's claim
            $table->text('description');
            $table->integer('claimed_amount_cents')->nullable();

            // Optional snapshot evidence reference
            $table->foreignId('evidence_snapshot_id')
                ->nullable()
                ->constrained('purchase_evidence')
                ->nullOnDelete();

            // Dispute Lifecycle Status
            $table->string('status', 32)->default('opened')->index();

            // Merchant Response
            $table->text('merchant_response')->nullable();
            $table->timestamp('merchant_responded_at')->nullable();

            // Final Adjudication / Resolution
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Query indexes
            $table->index(['customer_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index(['purchase_intent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_disputes');
    }
};
