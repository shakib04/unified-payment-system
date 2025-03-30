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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('payment_gateway_id')->constrained();
            $table->string('name');
            $table->string('type'); // 'card', 'bank_account', 'mobile_wallet', etc.
            $table->string('provider')->nullable(); // 'visa', 'mastercard', 'bkash', 'nagad', etc.
            $table->string('account_number')->nullable();
            $table->string('last_four')->nullable();
            $table->string('expiry_month')->nullable();
            $table->string('expiry_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta_data')->nullable(); // Additional data
            $table->timestamps();

            // Index for faster lookups
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
