<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\PaymentMethod;
use App\Models\ScheduledPayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScheduledPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = ScheduledPayment::where('user_id', Auth::id())
            ->with(['paymentMethod', 'bankAccount', 'category']);

        $schedulePayments = $query->get();

        return response()->json([
            'success' => true,
            'data' => $schedulePayments
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //amount	88
        //bank_account_id	""
        //bill_id	""
        //currency	"BDT"
        //description	""
        //end_date	""
        //frequency	"weekly"
        //name	"Transfer"
        //payment_method_id	1
        //payment_type	"transfer"
        //recipient_account	"88888"
        //recipient_bank	"jjjj"
        //recipient_name	"kk"
        //start_date	"2025-03-27"

        $validated = $request->validate([
            'name' => 'required|string',
            'recipient_name' => 'required|string',
            'recipient_account' => 'required|string',
            'recipient_bank' => 'nullable|string|max:255',
            'payment_for' => 'nullable|string',
            'payment_type' => 'required|string',
            'frequency' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
            'payment_method_id' => 'required_without:bank_account_id|exists:payment_methods,id,user_id,' . Auth::id(),
            'bank_account_id' => 'required_without:payment_method_id|exists:bank_accounts,id,user_id,' . Auth::id(),
            'bill_id' => 'nullable|exists:bills,id,user_id,' . Auth::id(),
        ]);

        try {
            DB::beginTransaction();

            // Generate unique transaction ID
            $transactionId = Str::uuid();

            // Create transaction record with 'pending' status
            $schedulePayment = new ScheduledPayment();
            $schedulePayment->user_id = Auth::id();
            $schedulePayment->name = $validated['name'];
            $schedulePayment->payment_method_id = $validated['payment_method_id'];
            $schedulePayment->amount = $validated['amount'];
            $schedulePayment->bank_account_id = $validated['bank_account_id'] ?? null;
            $schedulePayment->bill_id = $validated['bill_id'];
            $schedulePayment->description = $validated['description'];
            $schedulePayment->end_date = $validated['end_date'];
            $schedulePayment->frequency = $validated['frequency'];
            $schedulePayment->payment_type = $validated['payment_type'];
            $schedulePayment->recipient_account = $validated['recipient_account'];
            $schedulePayment->recipient_name = $validated['recipient_name'];
            $schedulePayment->start_date = $validated['start_date'];
            $schedulePayment->currency = 'BDT'; // Default currency

            $schedulePayment->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $schedulePayment,
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }


    /**
     * Pause a scheduled payment
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function pause(Request $request, $id)
    {
        try {
            $scheduledPayment = ScheduledPayment::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Check if payment can be paused
            if ($scheduledPayment->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active payments can be paused'
                ], 400);
            }

            // Update status
            $scheduledPayment->status = 'paused';
            $scheduledPayment->updated_at = now();
            $scheduledPayment->save();

            return response()->json([
                'success' => true,
                'message' => 'Scheduled payment paused successfully',
                'data' => $scheduledPayment
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled payment not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause scheduled payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Resume a scheduled payment
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume($id)
    {
        try {
            $scheduledPayment = ScheduledPayment::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Check if payment can be resumed
            if ($scheduledPayment->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only paused payments can be resumed'
                ], 400);
            }

            // If end date is set and already passed, don't resume
            if ($scheduledPayment->end_date && now()->greaterThan($scheduledPayment->end_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot resume a payment schedule that has already ended'
                ], 400);
            }

            // Update status and calculate next payment date
            $scheduledPayment->status = 'active';

            // Recalculate next payment date based on frequency and current date
            $nextPaymentDate = now();

            switch ($scheduledPayment->frequency) {
                case 'weekly':
                    $nextPaymentDate = $nextPaymentDate->addWeek();
                    break;
                case 'monthly':
                    $nextPaymentDate = $nextPaymentDate->addMonth();
                    break;
                case 'quarterly':
                    $nextPaymentDate = $nextPaymentDate->addMonths(3);
                    break;
                case 'yearly':
                    $nextPaymentDate = $nextPaymentDate->addYear();
                    break;
            }

            // Only update next_scheduled if it's a recurring payment
            if ($scheduledPayment->frequency !== 'one-time') {
                $scheduledPayment->next_scheduled = $nextPaymentDate;
            }

            $scheduledPayment->updated_at = now();
            $scheduledPayment->save();

            return response()->json([
                'success' => true,
                'message' => 'Scheduled payment resumed successfully',
                'data' => $scheduledPayment
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled payment not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume scheduled payment',
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
        //
    }
}
