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
     * Adds resolution state to verification_decisions and creates the
     * append-only decision_resolution_audit_logs table.
     */
    public function up(): void
    {
        // 1. Add resolution columns to verification_decisions
        Schema::table('verification_decisions', function (Blueprint $table) {
            $table->string('resolution_status', 32)
                ->default('unresolved')
                ->after('hold_payment_capture')
                ->index();

            $table->timestamp('resolved_at')
                ->nullable()
                ->after('resolution_status');

            $table->foreignId('resolved_by')
                ->nullable()
                ->after('resolved_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // 2. Create append-only decision_resolution_audit_logs table
        Schema::create('decision_resolution_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('verification_decision_id')
                ->constrained('verification_decisions')
                ->restrictOnDelete();

            // Action: hold_placed | customer_confirmed | customer_rejected | capture_released | capture_voided | hold_expired
            $table->string('action', 32)->index();

            // Actor Type: customer | merchant | admin | system
            $table->string('actor_type', 32)->index();

            // Actor user ID (null if automated system action)
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reason')->nullable();
            $table->json('metadata_json')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Index for querying history of a decision
            $table->index(['verification_decision_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_resolution_audit_logs');

        Schema::table('verification_decisions', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolution_status', 'resolved_at', 'resolved_by']);
        });
    }
};
