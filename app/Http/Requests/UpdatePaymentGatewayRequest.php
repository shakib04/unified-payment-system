<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePaymentGatewayRequest extends StorePaymentGatewayRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Modify unique rules to ignore the current gateway
        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('payment_gateways', 'name')->ignore($this->route('payment_gateway')->id)
        ];

        $rules['code'] = [
            'required',
            'string',
            'max:50',
            'regex:/^[a-z0-9_-]+$/',
            Rule::unique('payment_gateways', 'code')->ignore($this->route('payment_gateway')->id)
        ];

        // Make logo optional for update
        $rules['logo'] = 'nullable|image|max:2048';

        return $rules;
    }
}
