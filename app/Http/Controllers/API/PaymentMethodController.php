<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Add this import

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the payment methods.
     */
    public function index(): AnonymousResourceCollection
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
            ->with('paymentGateway') // Eager load payment gateway
            ->get();

        return PaymentMethodResource::collection($paymentMethods);
    }

    /**
     * Store a newly created payment method.
     */
    public function store(StorePaymentMethodRequest $request): PaymentMethodResource
    {
        return DB::transaction(function () use ($request) {
            $validatedData = $request->validated();

            // Attach the current user to the payment method
            $validatedData['user_id'] = Auth::id();

            // If this is the first payment method or is set as default
            $existingDefaultCount = PaymentMethod::where('user_id', Auth::id())
                ->where('is_default', true)
                ->count();

            // Set as default if first method or explicitly requested
            $validatedData['is_default'] = $existingDefaultCount === 0 ||
                ($request->has('is_default') && $request->input('is_default'));

            // If setting this as default, unset other default methods
            if ($validatedData['is_default']) {
                PaymentMethod::where('user_id', Auth::id())
                    ->update(['is_default' => false]);
            }

            // For card methods, process card-specific details
            if ($validatedData['type'] === 'card') {
                // Extract last 4 digits
                $validatedData['last_four'] = substr($validatedData['card_number'], -4);

                // Determine card brand (you might want to expand this logic)
                $validatedData['card_brand'] = $this->determineCardBrand($validatedData['card_number']);

                // Remove full card number from storage
                unset($validatedData['card_number']);
            }

            // For MFS methods, ensure payment gateway is set
            if ($validatedData['type'] === 'mfs' && isset($validatedData['payment_gateway_id'])) {
                $paymentGateway = PaymentGateway::findOrFail($validatedData['payment_gateway_id']);
            }

            $validatedData['name'] = 'ss';

            $paymentMethod = PaymentMethod::create($validatedData);

            return new PaymentMethodResource($paymentMethod->load('paymentGateway'));
        });
    }

    /**
     * Determine card brand based on card number.
     */
    private function determineCardBrand(string $cardNumber): string
    {
        // Simple card brand detection (you can expand this)
        $patterns = [
            'visa' => '/^4/',
            'mastercard' => '/^5[1-5]/',
            'amex' => '/^3[47]/',
            'discover' => '/^6(?:011|5)/'
        ];

        foreach ($patterns as $brand => $pattern) {
            if (preg_match($pattern, $cardNumber)) {
                return $brand;
            }
        }

        return 'unknown';
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        // Ensure the payment method belongs to the authenticated user
        //$this->authorize('delete', $paymentMethod);

        // Prevent deletion of the default payment method
        if ($paymentMethod->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default payment method.'
            ], 400);
        }

        $paymentMethod->delete();

        return response()->json([
            'message' => 'Payment method deleted successfully.'
        ]);
    }

    /**
     * Set a payment method as the default for the user.
     */
    public function setDefault(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $paymentMethod = PaymentMethod::findOrFail($id);

            // Ensure the payment method belongs to the authenticated user
            $this->authorize('update', $paymentMethod);

            // Remove the current default payment method
            PaymentMethod::where('user_id', Auth::id())
                ->update(['is_default' => false]);

            // Set the new default payment method
            $paymentMethod->update(['is_default' => true]);

            return response()->json([
                'message' => 'Default payment method updated successfully.',
                'payment_method' => new PaymentMethodResource($paymentMethod)
            ]);
        });
    }
}
