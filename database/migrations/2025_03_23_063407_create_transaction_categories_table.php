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
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('transaction_categories')->onDelete('cascade');
            $table->timestamps();

            // Create an index on the code column for faster lookups
            $table->index('code');
        });

        // Insert default parent categories
        DB::table('transaction_categories')->insert([
            [
                'name' => 'Income',
                'code' => 'income',
                'description' => 'All income categories',
                'icon' => 'arrow-down',
                'color' => '#4CAF50',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Expense',
                'code' => 'expense',
                'description' => 'All expense categories',
                'icon' => 'arrow-up',
                'color' => '#F44336',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transfer',
                'code' => 'transfer',
                'description' => 'Transfers between accounts',
                'icon' => 'repeat',
                'color' => '#2196F3',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert common subcategories for income
        $incomeId = DB::table('transaction_categories')->where('code', 'income')->value('id');
        DB::table('transaction_categories')->insert([
            [
                'name' => 'Salary',
                'code' => 'salary',
                'description' => 'Regular employment income',
                'icon' => 'briefcase',
                'color' => '#66BB6A',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $incomeId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Investments',
                'code' => 'investments',
                'description' => 'Income from investments',
                'icon' => 'trending-up',
                'color' => '#26A69A',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $incomeId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other Income',
                'code' => 'other_income',
                'description' => 'Miscellaneous income',
                'icon' => 'plus-circle',
                'color' => '#9CCC65',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $incomeId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert common subcategories for expense
        $expenseId = DB::table('transaction_categories')->where('code', 'expense')->value('id');
        DB::table('transaction_categories')->insert([
            [
                'name' => 'Housing',
                'code' => 'housing',
                'description' => 'Rent, mortgage, utilities, etc.',
                'icon' => 'home',
                'color' => '#EF5350',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $expenseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Food',
                'code' => 'food',
                'description' => 'Groceries, restaurants, etc.',
                'icon' => 'shopping-cart',
                'color' => '#FF7043',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $expenseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transportation',
                'code' => 'transportation',
                'description' => 'Car, public transport, etc.',
                'icon' => 'car',
                'color' => '#FF5722',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $expenseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Entertainment',
                'code' => 'entertainment',
                'description' => 'Movies, games, etc.',
                'icon' => 'film',
                'color' => '#FFA726',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $expenseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other Expenses',
                'code' => 'other_expenses',
                'description' => 'Miscellaneous expenses',
                'icon' => 'minus-circle',
                'color' => '#FF8A65',
                'is_active' => true,
                'is_system' => true,
                'parent_id' => $expenseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
