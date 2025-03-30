<?php

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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();

            // Set a default base URL
            $table->string('base_url')->default('https://default-gateway-url.com');

            // Encrypted API credentials storage
            $table->json('api_credentials')->nullable();
            $table->json('webhook_urls')->nullable();

            // Flags for gateway capabilities
            $table->boolean('supports_recurring')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
