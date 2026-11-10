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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('intentguard_id', 32)->unique()->index();
            $table->string('business_name', 191);
            $table->string('legal_name', 191)->nullable();
            $table->string('business_registration_number', 64)->unique()->index();
            $table->string('tax_id', 64)->nullable();
            $table->string('business_type', 32);
            $table->string('contact_name', 191);
            $table->string('contact_email', 191)->unique()->index();
            $table->string('contact_phone', 32);
            $table->string('website_url', 255);
            $table->string('platform_type', 32);
            $table->string('status', 32)->default('pending_review')->index();
            $table->decimal('compliance_score', 5, 2)->default(100.00);
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
