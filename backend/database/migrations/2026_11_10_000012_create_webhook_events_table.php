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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32)->default('paypal')->index();
            $table->string('event_id', 128)->unique();
            $table->string('event_type', 64)->index();
            $table->string('resource_type', 64)->nullable();
            $table->string('resource_id', 64)->nullable()->index();
            $table->boolean('signature_verified')->default(false);
            $table->string('status', 32)->default('received')->index();
            $table->json('payload_json');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
