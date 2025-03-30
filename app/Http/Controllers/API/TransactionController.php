<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\PaymentGateway;
use App\Services\PaymentGateways\BkashService;
use App\Services\PaymentGateways\RocketService;
use App\Services\PaymentGateways\NagadService;
use App\Services\PaymentGateways\SslCommerzService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\TransactionProcessed;

class TransactionController extends Controller
{
    /**
     * Display a listing of the transactions for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->with(['paymentMethod', 'bankAccount', 'category']);

        // Filter by transaction type
        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        // Filter by bank account
        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        // Filter by amount range
        if ($request->has('min_amount') && $request->has('max_amount')) {
            $query->whereBetween('amount', [$request->min_amount, $request->max_amount]);
        }

        // Sort by field
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Store a newly created transaction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|string|in:payment,transfer,deposit,withdrawal',
            'payment_for' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
            'payment_method_id' => 'required_without:bank_account_id|exists:payment_methods,id,user_id,' . Auth::id(),
            'bank_account_id' => 'required_without:payment_method_id|exists:bank_accounts,id,user_id,' . Auth::id(),
            'recipient_name' => 'required_if:transaction_type,transfer|string|max:255',
            'recipient_account' => 'required_if:transaction_type,transfer|string|max:255',
            'recipient_bank' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:transaction_categories,id',
            'bill_id' => 'nullable|exists:bills,id,user_id,' . Auth::id(),
        ]);

        try {
            DB::beginTransaction();

            // Generate unique transaction ID
            $transactionId = Str::uuid();

            // Create transaction record with 'pending' status
            $transaction = new Transaction();
            $transaction->transaction_id = $transactionId;
            $transaction->user_id = Auth::id();
            $transaction->transaction_type = $validated['transaction_type'];
            $transaction->payment_for = $validated['payment_for'] ?? null;
            $transaction->amount = $validated['amount'];
            $transaction->description = $validated['description'] ?? null;
            $transaction->status = 'pending';
            $transaction->currency = 'BDT'; // Default currency

            if (isset($validated['payment_method_id'])) {
                $transaction->payment_method_id = $validated['payment_method_id'];
                $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

                // Process payment through the appropriate gateway
                $gatewayService = $this->getPaymentGatewayService($paymentMethod->paymentGateway->code);

                $metadata = [
                    'transaction_id' => (string) $transactionId,
                    'customer_name' => Auth::user()->name,
                    'customer_email' => Auth::user()->email,
                    'customer_phone' => Auth::user()->phone,
                    'payment_for' => $validated['payment_for'] ?? 'General Payment',
                ];

                $paymentResponse = $gatewayService->initiatePayment(
                    $validated['amount'],
                    'BDT',
                    $metadata
                );

                if (!isset($paymentResponse['success']) || !$paymentResponse['success']) {
                    throw new \Exception('Payment initiation failed: ' . ($paymentResponse['message'] ?? 'Unknown error'));
                }

                $transaction->reference_id = $paymentResponse['paymentID'] ?? null;
                $transaction->response_data = json_encode($paymentResponse);
            } elseif (isset($validated['bank_account_id'])) {
                $transaction->bank_account_id = $validated['bank_account_id'];
                // Handle bank transfer logic here
            }

            if (isset($validated['recipient_name'])) {
                $transaction->recipient_name = $validated['recipient_name'];
            }

            if (isset($validated['recipient_account'])) {
                $transaction->recipient_account = $validated['recipient_account'];
            }

            if (isset($validated['recipient_bank'])) {
                $transaction->recipient_bank = $validated['recipient_bank'];
            }

            if (isset($validated['category_id'])) {
                $transaction->category_id = $validated['category_id'];
            }

            // If it's a bill payment, associate it with the bill
            if (isset($validated['bill_id'])) {
                $bill = Bill::findOrFail($validated['bill_id']);
                $transaction->payment_for = 'bill_payment';
                $transaction->description = 'Payment for ' . $bill->biller_name . ' - ' . $bill->account_number;
            }

            $transaction->save();

            DB::commit();

            // For payment gateways that require redirect, return the redirect URL
            if (isset($paymentResponse['GatewayPageURL'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction initiated',
                    'redirect_url' => $paymentResponse['GatewayPageURL'],
                    'transaction_id' => $transactionId
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction,
                'payment_data' => $paymentResponse ?? null
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transaction = Transaction::where('user_id', Auth::id())
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('transaction_id', $id);
            })
            ->with(['paymentMethod', 'bankAccount', 'category'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Update the transaction status after payment gateway callback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $gateway
     * @return \Illuminate\Http\Response
     */
    public function handleCallback(Request $request, $gateway)
    {
        Log::info('Payment callback received from: ' . $gateway, $request->all());

        try {
            $gatewayService = $this->getPaymentGatewayService($gateway);

            switch ($gateway) {
                case 'bkash':
                    $paymentId = $request->input('paymentID');
                    $status = $gatewayService->getPaymentStatus($paymentId);

                    $transaction = Transaction::where('reference_id', $paymentId)->firstOrFail();
                    $transaction->status = $this->mapGatewayStatusToAppStatus($status, $gateway);
                    $transaction->gateway_reference = $request->input('trxID') ?? null;
                    $transaction->response_data = json_encode($request->all());

                    if ($transaction->status === 'completed') {
                        $transaction->processed_at = now();
                    }

                    $transaction->save();

                    // Trigger transaction processed event
                    event(new TransactionProcessed($transaction));

                    return redirect()->route('transactions.status', ['id' => $transaction->transaction_id]);

                case 'sslcommerz':
                    $validationId = $request->input('val_id');
                    $status = $gatewayService->getPaymentStatus($validationId);

                    $transaction = Transaction::where('transaction_id', $request->input('tran_id'))->firstOrFail();
                    $transaction->status = $this->mapGatewayStatusToAppStatus($status, $gateway);
                    $transaction->gateway_reference = $validationId;
                    $transaction->response_data = json_encode($request->all());

                    if ($transaction->status === 'completed') {
                        $transaction->processed_at = now();
                    }

                    $transaction->save();

                    // Trigger transaction processed event
                    event(new TransactionProcessed($transaction));

                    return redirect()->route('transactions.status', ['id' => $transaction->transaction_id]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported payment gateway'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment callback processing failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check the status of a transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function checkStatus(Request $request, $id)
    {
        $transaction = Transaction::where('user_id', Auth::id())
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('transaction_id', $id);
            })
            ->firstOrFail();

        // For transactions that are still pending, check with the gateway
        if ($transaction->status === 'pending' && $transaction->payment_method_id) {
            $paymentMethod = PaymentMethod::with('paymentGateway')->findOrFail($transaction->payment_method_id);
            $gatewayService = $this->getPaymentGatewayService($paymentMethod->paymentGateway->code);

            if ($transaction->reference_id) {
                $gatewayStatus = $gatewayService->getPaymentStatus($transaction->reference_id);
                $transaction->status = $this->mapGatewayStatusToAppStatus($gatewayStatus, $paymentMethod->paymentGateway->code);

                if ($transaction->status === 'completed') {
                    $transaction->processed_at = now();

                    // Trigger transaction processed event
                    event(new TransactionProcessed($transaction));
                }

                $transaction->save();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'processed_at' => $transaction->processed_at
            ]
        ]);
    }

    /**
     * Get the appropriate payment gateway service.
     *
     * @param  string  $gatewayCode
     * @return \App\Services\PaymentGateways\PaymentGatewayInterface
     */
    private function getPaymentGatewayService($gatewayCode)
    {
        switch ($gatewayCode) {
            case 'bkash':
                return new BkashService();
            case 'rocket':
                return new RocketService();
            case 'nagad':
                return new NagadService();
            case 'sslcommerz':
                return new SslCommerzService();
            default:
                throw new \Exception('Unsupported payment gateway: ' . $gatewayCode);
        }
    }

    /**
     * Map gateway-specific status to application status.
     *
     * @param  string  $gatewayStatus
     * @param  string  $gateway
     * @return string
     */
    private function mapGatewayStatusToAppStatus($gatewayStatus, $gateway)
    {
        switch ($gateway) {
            case 'bkash':
                $statusMap = [
                    'Completed' => 'completed',
                    'Processing' => 'processing',
                    'Initiated' => 'pending',
                    'Cancelled' => 'failed',
                    'Failed' => 'failed',
                ];
                return $statusMap[$gatewayStatus] ?? 'pending';

            case 'sslcommerz':
                $statusMap = [
                    'VALID' => 'completed',
                    'VALIDATED' => 'completed',
                    'PENDING' => 'pending',
                    'FAILED' => 'failed',
                    'CANCELLED' => 'failed',
                ];
                return $statusMap[$gatewayStatus] ?? 'pending';

            default:
                return strtolower($gatewayStatus);
        }
    }
}
