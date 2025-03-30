<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['mfs', 'card'])],
            'name' => 'sometimes|string|max:255',
            'is_default' => 'sometimes|boolean',

            // MFS specific rules
            'payment_gateway_id' => [
                'required_if:type,mfs',
                'exists:payment_gateways,id'
            ],
            'account_number' => [
                'required_if:type,mfs',
                'string',
                'max:255'
            ],

            // Card specific rules
            'card_number' => [
                'required_if:type,card',
                'string',
                'size:16' // Adjust as needed
            ],
            'expiry_month' => [
                'required_if:type,card',
                'integer',
                'between:1,12'
            ],
            'expiry_year' => [
                'required_if:type,card',
                'integer',
                'min:' . date('Y')
            ],
            'cvv' => [
                'required_if:type,card',
                'string',
                'size:3' // or 4 for Amex
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid payment method type. Must be either "mfs" or "card".',
            'payment_gateway_id.required_if' => 'Payment gateway is required for mobile financial services.',
            'account_number.required_if' => 'Account number is required for mobile financial services.',
            'card_number.required_if' => 'Card number is required for card payments.',
            'expiry_month.required_if' => 'Expiry month is required for card payments.',
            'expiry_year.required_if' => 'Expiry year is required for card payments.',
            'cvv.required_if' => 'CVV is required for card payments.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure type is set
        $this->merge([
            'type' => $this->input('type', 'mfs')
        ]);
    }
}
