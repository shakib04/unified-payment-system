<?php

use App\Http\Controllers\API\BillerController;
use App\Http\Controllers\API\PaymentMethodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BankAccountController;
use App\Http\Controllers\API\PaymentGatewayController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\ScheduledPaymentController;
use App\Http\Controllers\API\BeneficiaryController;
use App\Http\Controllers\API\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Payment gateway callbacks
Route::prefix('payment-callback')->group(function () {
    Route::any('/bkash', [TransactionController::class, 'handleCallback'])->name('payment.bkash.callback');
    Route::any('/rocket', [TransactionController::class, 'handleCallback'])->name('payment.rocket.callback');
    Route::any('/nagad', [TransactionController::class, 'handleCallback'])->name('payment.nagad.callback');
    Route::any('/sslcommerz/success', [TransactionController::class, 'handleCallback'])->name('payment.sslcommerz.success');
    Route::any('/sslcommerz/fail', [TransactionController::class, 'handleCallback'])->name('payment.sslcommerz.fail');
    Route::any('/sslcommerz/cancel', [TransactionController::class, 'handleCallback'])->name('payment.sslcommerz.cancel');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/enable-two-factor', [AuthController::class, 'enableTwoFactor']);
    Route::post('/disable-two-factor', [AuthController::class, 'disableTwoFactor']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/transactions-summary', [DashboardController::class, 'transactionsSummary']);
    Route::get('/dashboard/upcoming-bills', [DashboardController::class, 'upcomingBills']);
    Route::get('/dashboard/recent-transactions', [DashboardController::class, 'recentTransactions']);

    // Bank Accounts
    Route::apiResource('bank-accounts', BankAccountController::class);
    Route::post('/bank-accounts/{id}/verify', [BankAccountController::class, 'verify']);
    Route::post('/bank-accounts/{id}/set-primary', [BankAccountController::class, 'setPrimary']);

    // Payment Methods
    Route::apiResource('payment-methods', PaymentMethodController::class);
    Route::post('/payment-methods/{id}/set-default', [PaymentMethodController::class, 'setDefault']);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/transactions/{id}/status', [TransactionController::class, 'checkStatus'])->name('transactions.status');
    Route::get('/transactions/filter/by-date', [TransactionController::class, 'filterByDate']);
    Route::get('/transactions/filter/by-type', [TransactionController::class, 'filterByType']);
    Route::get('/transactions/filter/by-status', [TransactionController::class, 'filterByStatus']);

    // Bills
    Route::apiResource('bills', BillController::class);
    Route::post('/bills/{id}/pay', [BillController::class, 'pay']);
    Route::post('/bills/{id}/toggle-auto-pay', [BillController::class, 'toggleAutoPay']);
    Route::get('/bills/due-soon', [BillController::class, 'getDueSoon']);
    Route::get('/bills/by-type', [BillController::class, 'getByType']);

    // Biller routes
    Route::get('/billers', [BillerController::class, 'index']);
    Route::post('/billers', [BillerController::class, 'store']);
    Route::get('/billers/{id}', [BillerController::class, 'show']);
    Route::put('/billers/{id}', [BillerController::class, 'update']);
    Route::delete('/billers/{id}', [BillerController::class, 'destroy']);
    Route::get('/billers/category/{categoryId}', [BillerController::class, 'getByCategory']);

    // Scheduled Payments
    Route::apiResource('scheduled-payments', ScheduledPaymentController::class);
    Route::post('/scheduled-payments/{id}/pause', [ScheduledPaymentController::class, 'pause']);
    Route::post('/scheduled-payments/{id}/resume', [ScheduledPaymentController::class, 'resume']);
    Route::post('/scheduled-payments/{id}/cancel', [ScheduledPaymentController::class, 'cancel']);

    // Beneficiaries
    Route::apiResource('beneficiaries', BeneficiaryController::class);
    Route::post('/beneficiaries/{id}/quick-transfer', [BeneficiaryController::class, 'quickTransfer']);

    // Payment Gateways
    Route::get('/payment-gateways', [PaymentGatewayController::class, 'index']);
    Route::get('/payment-gateways/{code}', [PaymentGatewayController::class, 'show']);

    // Utility Payments
    Route::post('/utilities/mobile-recharge', [UtilityController::class, 'mobileRecharge']);
    Route::post('/utilities/desco-bill', [UtilityController::class, 'descoBill']);
    Route::post('/utilities/wasa-bill', [UtilityController::class, 'wasaBill']);
    Route::post('/utilities/titas-gas', [UtilityController::class, 'titasGas']);
    Route::post('/utilities/internet-bill', [UtilityController::class, 'internetBill']);

    // Reports
    Route::get('/reports/spending-by-category', [ReportController::class, 'spendingByCategory']);
    Route::get('/reports/monthly-summary', [ReportController::class, 'monthlySummary']);
    Route::get('/reports/transaction-history', [ReportController::class, 'transactionHistory']);
    Route::get('/reports/bills-summary', [ReportController::class, 'billsSummary']);
});
