<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->string('recipient_name');
            $table->string('recipient_account');
            $table->string('recipient_bank')->nullable();
            $table->string('payment_type'); // 'bill', 'transfer', 'subscription'
            $table->foreignId('bill_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('BDT');
            $table->string('frequency'); // 'one-time', 'weekly', 'monthly', 'quarterly', 'yearly'
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // 'active', 'paused', 'completed', 'failed'
            $table->text('description')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->date('last_processed')->nullable();
            $table->date('next_scheduled')->nullable();
            $table->integer('times_processed')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_payments');
    }
};
