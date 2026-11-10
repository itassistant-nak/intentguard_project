<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_intent_id')->constrained('purchase_intents')->cascadeOnDelete();
            $table->foreignId('verification_decision_id')->nullable()->constrained('verification_decisions')->nullOnDelete();
            $table->string('gateway', 32)->default('paypal');
            $table->string('order_id', 64)->index();
            $table->string('authorization_id', 64)->nullable()->index();
            $table->string('capture_id', 64)->nullable()->index();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('status', 32)->default('created')->index();
            $table->string('idempotency_key', 128)->unique();
            $table->string('customer_payer_id', 64)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_authorizations');
    }
};
