<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing categories
        TransactionCategory::truncate();

        // Create parent categories first
        $incomeParent = TransactionCategory::create([
            'name' => 'Income',
            'code' => 'income',
            'description' => 'All income sources',
            'icon' => 'fas fa-money-bill-wave',
            'color' => '#28a745',
            'is_active' => true,
            'is_system' => true
        ]);

        $expenseParent = TransactionCategory::create([
            'name' => 'Expenses',
            'code' => 'expense',
            'description' => 'All expense categories',
            'icon' => 'fas fa-shopping-cart',
            'color' => '#dc3545',
            'is_active' => true,
            'is_system' => true
        ]);

        // Income subcategories
        $incomeCategories = [
            [
                'name' => 'Salary',
                'description' => 'Monthly salary and wages',
                'icon' => 'fas fa-briefcase',
                'color' => '#17a2b8',
            ],
            [
                'name' => 'Freelance',
                'description' => 'Income from freelance work',
                'icon' => 'fas fa-laptop',
                'color' => '#20c997',
            ],
            [
                'name' => 'Investment Returns',
                'description' => 'Returns from investments',
                'icon' => 'fas fa-chart-line',
                'color' => '#ffc107',
            ],
            [
                'name' => 'Other Income',
                'description' => 'Miscellaneous income sources',
                'icon' => 'fas fa-coins',
                'color' => '#6f42c1',
            ]
        ];

        // Expense subcategories
        $expenseCategories = [
            [
                'name' => 'Groceries',
                'description' => 'Food and household supplies',
                'icon' => 'fas fa-shopping-basket',
                'color' => '#28a745',
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, gas bills',
                'icon' => 'fas fa-bolt',
                'color' => '#17a2b8',
            ],
            [
                'name' => 'Rent',
                'description' => 'Housing rent',
                'icon' => 'fas fa-home',
                'color' => '#dc3545',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Public transport, fuel, vehicle maintenance',
                'icon' => 'fas fa-bus',
                'color' => '#ffc107',
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movies, dining out, leisure activities',
                'icon' => 'fas fa-gamepad',
                'color' => '#6f42c1',
            ],
            [
                'name' => 'Other Expenses',
                'description' => 'Miscellaneous expenses',
                'icon' => 'fas fa-ellipsis-h',
                'color' => '#343a40',
            ]
        ];

        // Create income subcategories
        foreach ($incomeCategories as $category) {
            TransactionCategory::create(array_merge($category, [
                'parent_id' => $incomeParent->id,
                'is_active' => true,
                'is_system' => false,
                'code' => 'income'
            ]));
        }

        // Create expense subcategories
        foreach ($expenseCategories as $category) {
            TransactionCategory::create(array_merge($category, [
                'parent_id' => $expenseParent->id,
                'is_active' => true,
                'is_system' => false,
                'code' => 'expense'
            ]));
        }
    }
}
