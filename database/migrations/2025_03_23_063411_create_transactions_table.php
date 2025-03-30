<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('payment_method_id')->nullable()->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories');
            $table->foreignId('scheduled_payment_id')->nullable()->constrained();
            $table->string('transaction_type'); // 'payment', 'transfer', 'deposit', 'withdrawal'
            $table->string('payment_for')->nullable(); // 'utility', 'mobile_recharge', 'ecommerce', etc.
            $table->string('recipient_name')->nullable();
            $table->string('recipient_account')->nullable();
            $table->string('recipient_bank')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('currency')->default('BDT');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed', 'refunded'
            $table->text('description')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('response_data')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
