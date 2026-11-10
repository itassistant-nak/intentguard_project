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
        Schema::create('merchant_agreement_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('merchant_agreement_id')->constrained('merchant_agreements')->restrictOnDelete();
            $table->string('signatory_name', 191);
            $table->string('signatory_title', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->char('terms_digest_snapshot', 64);
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['merchant_id', 'merchant_agreement_id'], 'merchant_agreement_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_agreement_acceptances');
    }
};
