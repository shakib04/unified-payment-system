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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories');
            $table->foreignId('payment_method_id')->nullable()->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->string('name');
            $table->string('bill_type'); // 'utility', 'subscription', 'loan', 'rent', 'other'
            $table->string('provider')->nullable();
            $table->string('account_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 12, 2)->nullable(); // Can be null if variable amount
            $table->string('currency')->default('BDT');
            $table->boolean('is_variable_amount')->default(false);
            $table->string('frequency'); // 'weekly', 'monthly', 'quarterly', 'yearly'
            $table->integer('due_day')->nullable(); // Day of month when bill is due
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('customer_service_phone')->nullable();
            $table->date('next_due_date');
            $table->date('last_paid_date')->nullable();
            $table->boolean('auto_pay')->default(false);
            $table->integer('reminder_days')->default(3); // Days before due date to send reminder
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'next_due_date']);
            $table->index(['user_id', 'bill_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
