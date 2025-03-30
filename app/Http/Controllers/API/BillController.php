<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentGateways\PaymentGatewayInterface;

class BillController extends Controller
{
    /**
     * Display a listing of bills for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Bill::where('user_id', Auth::id());

        // Filter by bill type
        if ($request->has('bill_type')) {
            $query->where('bill_type', $request->bill_type);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by biller name
        if ($request->has('biller_name')) {
            $query->where('biller_name', 'like', '%' . $request->biller_name . '%');
        }

        // Filter by due date range
        if ($request->has('due_from') && $request->has('due_to')) {
            $query->whereBetween('due_date', [$request->due_from, $request->due_to]);
        }

        // Filter bills with auto-pay enabled
        if ($request->has('auto_pay') && $request->auto_pay) {
            $query->where('auto_pay', true);
        }

        // Sort by field
        $sortField = $request->input('sort_by', 'due_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Eager load related models
        $query->with(['defaultPaymentMethod', 'defaultBankAccount']);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $bills = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bills
        ]);
    }

    /**
     * Store a newly created bill in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            //'biller_name' => 'required|string|max:255',
            'bill_type' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'meter_number' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'auto_pay' => 'boolean',
            'default_payment_method_id' => 'nullable|exists:payment_methods,id,user_id,' . Auth::id(),
            'default_bank_account_id' => 'nullable|exists:bank_accounts,id,user_id,' . Auth::id(),
        ]);

        try {
            $bill = new Bill();
            $bill->user_id = Auth::id();
            //$bill->biller_name = $validated['biller_name'];
            $bill->bill_type = $validated['bill_type'];
            $bill->name = 'nn';
            $bill->frequency = 'weekly';
            $bill->account_number = $validated['account_number'];
            //$bill->customer_id = $validated['customer_id'] ?? null;
            //$bill->meter_number = $validated['meter_number'] ?? null;
            $bill->amount = $validated['amount'] ?? null;
            //$bill->minimum_amount = $validated['minimum_amount'] ?? null;
            //$bill->due_date = $validated['due_date'] ?? null;
            $bill->next_due_date = '2025-03-17';
            //$bill->payment_status = 'unpaid';
            $bill->auto_pay = $validated['auto_pay'] ?? false;

            if (isset($validated['default_payment_method_id'])) {
                //$bill->default_payment_method_id = $validated['default_payment_method_id'];
            }

            if (isset($validated['default_bank_account_id'])) {
                //$bill->default_bank_account_id = $validated['default_bank_account_id'];
            }

            $bill->save();

            return response()->json([
                'success' => true,
                'message' => 'Bill created successfully',
                'data' => $bill
            ], 201);
        } catch (\Exception $e) {
            Log::error('Bill creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bill creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified bill.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $bill = Bill::where('user_id', Auth::id())
            ->with(['defaultPaymentMethod', 'defaultBankAccount'])
            ->findOrFail($id);

        // Get payment history
        $paymentHistory = Transaction::where('user_id', Auth::id())
            ->where('payment_for', 'bill_payment')
            ->whereJsonContains('description', $bill->biller_name)
            ->whereJsonContains('description', $bill->account_number)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'bill' => $bill,
                'payment_history' => $paymentHistory
            ]
        ]);
    }

    /**
     * Update the specified bill in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'biller_name' => 'string|max:255',
            'bill_type' => 'string|max:255',
            'account_number' => 'string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'meter_number' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'auto_pay' => 'boolean',
            'default_payment_method_id' => 'nullable|exists:payment_methods,id,user_id,' . Auth::id(),
            'default_bank_account_id' => 'nullable|exists:bank_accounts,id,user_id,' . Auth::id(),
        ]);

        try {
            $bill->fill($validated);
            $bill->save();

            return response()->json([
                'success' => true,
                'message' => 'Bill updated successfully',
                'data' => $bill
            ]);
        } catch (\Exception $e) {
            Log::error('Bill update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bill update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified bill from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        try {
            $bill->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bill deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Bill deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bill deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay a bill.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request, $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required_without:bank_account_id|exists:payment_methods,id,user_id,' . Auth::id(),
            'bank_account_id' => 'required_without:payment_method_id|exists:bank_accounts,id,user_id,' . Auth::id(),
        ]);

        // Ensure amount is valid
        if ($bill->amount && $validated['amount'] < $bill->minimum_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount is less than the minimum required amount'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create a new transaction for the bill payment
            $transactionController = new TransactionController();

            $transactionRequest = new Request([
                'transaction_type' => 'payment',
                'payment_for' => 'bill_payment',
                'amount' => $validated['amount'],
                'description' => 'Payment for ' . $bill->biller_name . ' - ' . $bill->account_number,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'bill_id' => $bill->id
            ]);

            $response = $transactionController->store($transactionRequest);
            $responseData = json_decode($response->getContent(), true);

            if (!$responseData['success']) {
                throw new \Exception('Transaction creation failed: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            // Update bill status
            $bill->payment_status = 'pending';
            $bill->save();

            DB::commit();

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bill payment failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bill payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle auto-pay for a bill.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleAutoPay(Request $request, $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'auto_pay' => 'required|boolean',
            'default_payment_method_id' => 'required_if:auto_pay,true|nullable|exists:payment_methods,id,user_id,' . Auth::id(),
            'default_bank_account_id' => 'required_if:auto_pay,true|nullable|exists:bank_accounts,id,user_id,' . Auth::id(),
        ]);

        // If enabling auto-pay, ensure either a payment method or bank account is specified
        if ($validated['auto_pay'] && !isset($validated['default_payment_method_id']) && !isset($validated['default_bank_account_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A default payment method or bank account is required for auto-pay'
            ], 400);
        }

        try {
            $bill->auto_pay = $validated['auto_pay'];

            if (isset($validated['default_payment_method_id'])) {
                $bill->default_payment_method_id = $validated['default_payment_method_id'];
            }

            if (isset($validated['default_bank_account_id'])) {
                $bill->default_bank_account_id = $validated['default_bank_account_id'];
            }

            $bill->save();

            return response()->json([
                'success' => true,
                'message' => 'Auto-pay ' . ($validated['auto_pay'] ? 'enabled' : 'disabled') . ' successfully',
                'data' => $bill
            ]);
        } catch (\Exception $e) {
            Log::error('Auto-pay update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Auto-pay update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bills due soon.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDueSoon(Request $request)
    {
        $days = $request->input('days', 7);

        $bills = Bill::where('user_id', Auth::id())
            ->where('payment_status', 'unpaid')
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->with(['defaultPaymentMethod', 'defaultBankAccount'])
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bills
        ]);
    }

    /**
     * Get bills by type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getByType(Request $request)
    {
        $types = $request->input('types', ['electricity', 'water', 'gas', 'internet', 'telephone']);

        $billsByType = [];

        foreach ($types as $type) {
            $bills = Bill::where('user_id', Auth::id())
                ->where('bill_type', $type)
                ->orderBy('due_date', 'asc')
                ->get();

            $billsByType[$type] = $bills;
        }

        return response()->json([
            'success' => true,
            'data' => $billsByType
        ]);
    }
}
