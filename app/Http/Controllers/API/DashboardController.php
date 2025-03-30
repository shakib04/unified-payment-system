<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UpcomingBillResource;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Provide an overview of key dashboard metrics.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Calculate financial metrics
        $metrics = [
            'total_balance' => $user->wallet_balance ?? 0,
            'total_income' => $this->calculateTotalAmount('income'),
            'total_expenses' => $this->calculateTotalAmount('expense'),
            'upcoming_bills_count' => $this->countUpcomingBills(),
            'recent_transactions_count' => $this->countRecentTransactions()
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }

    /**
     * Retrieve a summary of transaction statistics.
     */
    public function transactionsSummary(): JsonResponse
    {
        // Transactions summary for the last 30 days
        $startDate = Carbon::now()->subDays(30);

        // Fetch income and expense categories
        $incomeCategories = TransactionCategory::income()->pluck('id');
        $expenseCategories = TransactionCategory::expense()->pluck('id');

        // Prepare summary for income categories
        $incomeSummary = $this->calculateCategorySummary($incomeCategories, $startDate);

        // Prepare summary for expense categories
        $expenseSummary = $this->calculateCategorySummary($expenseCategories, $startDate);

        return response()->json([
            'success' => true,
            'data' => [
                'income' => $incomeSummary,
                'expenses' => $expenseSummary,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => Carbon::now()->toDateString()
                ]
            ]
        ]);
    }

    /**
     * Retrieve upcoming bills.
     */
    public function upcomingBills(): JsonResponse
    {
        $upcomingBills = $this->getUpcomingBillsQuery()->get();

        return response()->json([
            'success' => true,
            'data' => UpcomingBillResource::collection($upcomingBills)
        ]);
    }

    /**
     * Retrieve recent transactions.
     */
    public function recentTransactions(): JsonResponse
    {
        $recentTransactions = Transaction::where('user_id', Auth::id())
            ->with(['category', 'paymentMethod'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($recentTransactions)
        ]);
    }

    /**
     * Calculate total amount for a specific transaction type.
     */
    private function calculateTotalAmount(string $type): float
    {
        // Find categories for the given type
        $categoryIds = TransactionCategory::where('code', $type)
            ->orWhereHas('parent', function($query) use ($type) {
                $query->where('code', $type);
            })
            ->pluck('id');

        // Calculate total amount for the specific categories
        return Transaction::where('user_id', Auth::id())
            ->whereIn('category_id', $categoryIds)
            ->sum('amount');
    }

    /**
     * Calculate summary for specific categories.
     */
    private function calculateCategorySummary($categoryIds, $startDate): array
    {
        $summary = Transaction::where('user_id', Auth::id())
            ->where('created_at', '>=', $startDate)
            ->whereIn('category_id', $categoryIds)
            ->select(
                'category_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return $summary->mapWithKeys(function ($item) {
            return [$item->category->name => [
                'count' => $item->transaction_count,
                'total_amount' => number_format($item->total_amount, 2)
            ]];
        })->toArray();
    }

    /**
     * Count upcoming bills for the current user.
     */
    private function countUpcomingBills(): int
    {
        return $this->getUpcomingBillsQuery()->count();
    }

    /**
     * Count recent transactions for the current user.
     */
    private function countRecentTransactions(): int
    {
        return Transaction::where('user_id', Auth::id())
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
    }

    /**
     * Get a query for upcoming bills based on the available columns.
     */
    private function getUpcomingBillsQuery()
    {
        $query = Bill::where('user_id', Auth::id());

        // Check for different possible date-related columns
        $now = Carbon::now();
        $futureDate = $now->copy()->addDays(30);

        // Try different approaches based on available columns
        if ($this->columnExists('bills', 'due_date')) {
            // If due_date column exists, use it directly
            $query->whereDate('due_date', '<=', $futureDate);
        } elseif ($this->columnExists('bills', 'due_day')) {
            // If due_day is an integer representing the day of the month
            $query->where('due_day', '<=', $futureDate->day)
                ->where(function($q) use ($now) {
                    // Ensure the bill is for the current or future month
                    $q->whereRaw('EXTRACT(MONTH FROM CURRENT_DATE) = EXTRACT(MONTH FROM CURRENT_DATE)')
                        ->orWhereRaw('EXTRACT(MONTH FROM CURRENT_DATE) < EXTRACT(MONTH FROM CURRENT_DATE)');
                });
        } elseif ($this->columnExists('bills', 'bill_date')) {
            // If bill_date column exists
            $query->whereDate('bill_date', '<=', $futureDate);
        } else {
            // If no suitable date column is found, return an empty query
            $query->whereRaw('1=0');
        }

        return $query;
    }

    /**
     * Check if a column exists in a table.
     */
    private function columnExists(string $table, string $column): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }
}
