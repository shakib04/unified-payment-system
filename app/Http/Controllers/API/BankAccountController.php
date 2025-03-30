<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bankAccounts = BankAccount::where('user_id', Auth::id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('bank_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bankAccounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|string|in:savings,current,fixed',
            'branch_name' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Begin transaction to handle primary flag logic
        \DB::beginTransaction();

        try {
            // If this is the first account or is being set as primary,
            // ensure no other accounts are primary
            if ($request->is_primary || BankAccount::where('user_id', Auth::id())->count() === 0) {
                BankAccount::where('user_id', Auth::id())
                    ->update(['is_primary' => false]);

                $request->merge(['is_primary' => true]);
            }

            // Create the bank account
            $bankAccount = new BankAccount();
            $bankAccount->user_id = Auth::id();
            $bankAccount->bank_name = $request->bank_name;
            $bankAccount->account_number = $request->account_number;
            $bankAccount->account_name = $request->account_name;
            $bankAccount->account_type = $request->account_type;
            $bankAccount->branch_name = $request->branch_name;
            $bankAccount->routing_number = $request->routing_number;
            $bankAccount->swift_code = $request->swift_code;
            $bankAccount->is_primary = $request->is_primary ?? false;
            $bankAccount->is_active = true;
            //$bankAccount->balance = 0; // Initial balance

            $bankAccount->save();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank account created successfully',
                'data' => $bankAccount
            ], 201);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create bank account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $bankAccount = BankAccount::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        // Get recent transactions for this account
        $recentTransactions = $bankAccount->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'bank_account' => $bankAccount,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'bank_name' => 'sometimes|required|string|max:100',
            'account_name' => 'sometimes|required|string|max:100',
            'account_type' => 'sometimes|required|string|in:savings,current,fixed',
            'branch_name' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Begin transaction to handle primary flag logic
        \DB::beginTransaction();

        try {
            // If setting as primary, ensure no other accounts are primary
            if ($request->has('is_primary') && $request->is_primary) {
                BankAccount::where('user_id', Auth::id())
                    ->where('id', '!=', $id)
                    ->update(['is_primary' => false]);
            }

            // Update the account
            if ($request->has('bank_name')) {
                $bankAccount->bank_name = $request->bank_name;
            }

            if ($request->has('account_name')) {
                $bankAccount->account_name = $request->account_name;
            }

            if ($request->has('account_type')) {
                $bankAccount->account_type = $request->account_type;
            }

            if ($request->has('branch_name')) {
                $bankAccount->branch_name = $request->branch_name;
            }

            if ($request->has('routing_number')) {
                $bankAccount->routing_number = $request->routing_number;
            }

            if ($request->has('swift_code')) {
                $bankAccount->swift_code = $request->swift_code;
            }

            if ($request->has('is_active')) {
                $bankAccount->is_active = $request->is_active;
            }

            if ($request->has('is_primary')) {
                $bankAccount->is_primary = $request->is_primary;
            }

            $bankAccount->save();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank account updated successfully',
                'data' => $bankAccount
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update bank account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bankAccount = BankAccount::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        // Check if this account has any transactions
        if ($bankAccount->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a bank account with transactions. Please deactivate it instead.'
            ], 400);
        }

        try {
            // If we're deleting a primary account, find another one to make primary
            if ($bankAccount->is_primary) {
                $newPrimary = BankAccount::where('user_id', Auth::id())
                    ->where('id', '!=', $id)
                    ->where('is_active', true)
                    ->first();

                if ($newPrimary) {
                    $newPrimary->is_primary = true;
                    $newPrimary->save();
                }
            }

            $bankAccount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bank account deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bank account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set the specified bank account as primary.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setPrimary($id)
    {
        $bankAccount = BankAccount::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$bankAccount->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set an inactive bank account as primary'
            ], 400);
        }

        \DB::beginTransaction();

        try {
            // Remove primary flag from all other accounts
            BankAccount::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);

            // Set this account as primary
            $bankAccount->is_primary = true;
            $bankAccount->save();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank account set as primary successfully',
                'data' => $bankAccount
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to set bank account as primary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a bank account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function verify(Request $request, $id)
    {
        $bankAccount = BankAccount::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        // In a real implementation, this would connect to the bank's API
        // to verify the account details. For now, we'll simulate verification.

        // Simulate successful verification
        $bankAccount->is_active = true;
        $bankAccount->meta_data = [
            'verified_at' => now()->toIso8601String(),
            'verification_method' => 'manual'
        ];
        $bankAccount->save();

        return response()->json([
            'success' => true,
            'message' => 'Bank account verified successfully',
            'data' => $bankAccount
        ]);
    }
}
