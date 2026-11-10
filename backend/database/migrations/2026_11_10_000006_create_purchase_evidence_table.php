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
     * Creates the purchase_evidence table — the immutable chain-of-custody
     * log that records cryptographic snapshots of everything a customer
     * saw and agreed to before checkout was finalised.
     *
     * Records are APPEND-ONLY. No updates or deletes are permitted.
     * Evidence types:
     *   product_page     — Snapshot of the merchant product listing page at intent time.
     *   checkout_screen  — Snapshot of the checkout summary the customer accepted.
     *   payment_quote    — The exact payment amount/breakdown quoted before authorisation.
     *   receipt          — Post-capture payment receipt / order confirmation.
     */
    public function up(): void
    {
        Schema::create('purchase_evidence', function (Blueprint $table) {
            $table->id();

            // The intent this evidence belongs to
            $table->foreignId('purchase_intent_id')
                ->constrained('purchase_intents')
                ->restrictOnDelete();

            // Type of evidence captured
            $table->string('evidence_type', 32)->index();
            // Allowed values: product_page | checkout_screen | payment_quote | receipt

            // Source URL of the page/resource that was snapshotted
            $table->string('source_url', 2048)->nullable();

            // Raw text/HTML content of the snapshot (stored inline for small payloads)
            $table->longText('content_snapshot')->nullable();

            // Path to blob storage for large snapshots (e.g. S3, local disk)
            $table->string('content_snapshot_blob_path', 1024)->nullable();

            // SHA-256 hash of the raw content_snapshot — primary tamper-evidence field
            $table->char('content_hash', 64)->index();

            // Metadata captured alongside the snapshot
            $table->json('metadata_json')->nullable();
            // e.g. { "user_agent": "...", "viewport": "1440x900", "locale": "en-US" }

            // Submitter (the customer or system actor who submitted the evidence)
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Immutable capture timestamp (no updated_at — records must never change)
            $table->timestamp('captured_at')->useCurrent();

            // created_at only — no updated_at (append-only)
            $table->timestamp('created_at')->useCurrent();

            // Composite index for fetching all evidence for a given intent by type
            $table->index(['purchase_intent_id', 'evidence_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_evidence');
    }
};
