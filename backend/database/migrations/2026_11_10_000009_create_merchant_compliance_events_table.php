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
     * Creates the merchant_compliance_events table — logs every compliance telemetry
     * event (confirmed mismatches, delayed shipments, chargebacks, customer disputes),
     * score deductions, and appeal adjudication lifecycle.
     */
    public function up(): void
    {
        Schema::create('merchant_compliance_events', function (Blueprint $table) {
            $table->id();

            // Associated Merchant
            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->restrictOnDelete();

            // Optional linkage to purchase intent & verification decision
            $table->foreignId('purchase_intent_id')
                ->nullable()
                ->constrained('purchase_intents')
                ->nullOnDelete();

            $table->foreignId('verification_decision_id')
                ->nullable()
                ->constrained('verification_decisions')
                ->nullOnDelete();

            // Event Type
            $table->string('event_type', 32)->index();

            // Severity: critical | major | minor | positive
            $table->string('severity', 16)->default('minor');

            // Score Impact & State Trajectory
            $table->decimal('score_delta', 5, 2);
            $table->decimal('score_before', 5, 2);
            $table->decimal('score_after', 5, 2);

            // Audit & Explanatory Context
            $table->text('notes')->nullable();
            $table->string('evidence_reference', 1024)->nullable();

            // Appeal State Machine
            $table->string('appeal_status', 32)->default('none')->index();
            $table->text('appeal_reason')->nullable();
            $table->string('appeal_evidence_url', 1024)->nullable();
            $table->timestamp('appealed_at')->nullable();

            // Adjudication by IntentGuard Administrator
            $table->timestamp('adjudicated_at')->nullable();
            $table->foreignId('adjudicated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('adjudication_notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Performance & Query Indexes
            $table->index(['merchant_id', 'created_at']);
            $table->index(['merchant_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_compliance_events');
    }
};
