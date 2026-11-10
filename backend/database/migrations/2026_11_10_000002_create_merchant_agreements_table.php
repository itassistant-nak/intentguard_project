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
        Schema::create('merchant_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->unique()->index();
            $table->string('title', 191);
            $table->char('content_hash', 64);
            $table->text('product_accuracy_policy');
            $table->text('shipping_delivery_policy');
            $table->text('substitution_policy');
            $table->text('refund_replacement_policy');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('published_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_agreements');
    }
};
