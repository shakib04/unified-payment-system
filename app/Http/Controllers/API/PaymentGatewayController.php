<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentGatewayRequest;
use App\Http\Requests\UpdatePaymentGatewayRequest;
use App\Http\Resources\PaymentGatewayResource;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentGatewayController extends Controller
{
    /**
     * Display a listing of active payment gateways.
     */
    public function index(): AnonymousResourceCollection
    {
        $paymentGateways = PaymentGateway::active()
            ->orderBy('name')
            ->get();

        return PaymentGatewayResource::collection($paymentGateways);
    }

    /**
     * Display details of a specific payment gateway by its code.
     */
    public function show(string $code): JsonResponse
    {
        $paymentGateway = PaymentGateway::where('code', $code)->firstOrFail();

        return response()->json([
            'data' => new PaymentGatewayResource($paymentGateway)
        ]);
    }

    /**
     * Store a newly created payment gateway.
     */
    public function store(StorePaymentGatewayRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            // Validate the request
            $validatedData = $request->validated();

            // Handle logo upload if provided
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('gateways', 'public');
                $validatedData['logo_path'] = $logoPath;
            }

            // Create the payment gateway
            $paymentGateway = PaymentGateway::create($validatedData);

            return response()->json([
                'message' => 'Payment gateway created successfully',
                'data' => new PaymentGatewayResource($paymentGateway)
            ], 201);
        });
    }

    /**
     * Show the form for editing a payment gateway.
     */
    public function edit(PaymentGateway $paymentGateway): JsonResponse
    {
        return response()->json([
            'data' => new PaymentGatewayResource($paymentGateway)
        ]);
    }

    /**
     * Update the specified payment gateway.
     */
    public function update(UpdatePaymentGatewayRequest $request, PaymentGateway $paymentGateway): JsonResponse
    {
        return DB::transaction(function () use ($request, $paymentGateway) {
            // Validate the request
            $validatedData = $request->validated();

            // Handle logo update if a new logo is provided
            if ($request->hasFile('logo')) {
                // Delete the old logo if it exists
                if ($paymentGateway->logo_path) {
                    Storage::disk('public')->delete($paymentGateway->logo_path);
                }

                // Store the new logo
                $logoPath = $request->file('logo')->store('gateways', 'public');
                $validatedData['logo_path'] = $logoPath;
            }

            // Update the payment gateway
            $paymentGateway->update($validatedData);

            return response()->json([
                'message' => 'Payment gateway updated successfully',
                'data' => new PaymentGatewayResource($paymentGateway)
            ]);
        });
    }

    /**
     * Remove the specified payment gateway.
     */
    public function destroy(PaymentGateway $paymentGateway): JsonResponse
    {
        return DB::transaction(function () use ($paymentGateway) {
            // Prevent deletion of active gateways with existing transactions
            $transactionCount = $paymentGateway->transactions()->count();
            if ($transactionCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete payment gateway with existing transactions'
                ], 400);
            }

            // Delete the logo if it exists
            if ($paymentGateway->logo_path) {
                Storage::disk('public')->delete($paymentGateway->logo_path);
            }

            // Delete the payment gateway
            $paymentGateway->delete();

            return response()->json([
                'message' => 'Payment gateway deleted successfully'
            ]);
        });
    }
}
