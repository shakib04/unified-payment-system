<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentGatewayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Implement authorization logic, e.g., check if user is an admin
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:payment_gateways,name'
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:payment_gateways,code',
                'regex:/^[a-z0-9_-]+$/'
            ],
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048', // Max 2MB
            'base_url' => 'nullable|url',
            'supports_recurring' => 'boolean',
            'is_active' => 'boolean',
            'api_credentials' => 'nullable|array',
            'webhook_urls' => 'nullable|array'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A payment gateway with this name already exists.',
            'code.unique' => 'A payment gateway with this code already exists.',
            'code.regex' => 'The gateway code must contain only lowercase letters, numbers, underscores, and hyphens.',
            'logo.image' => 'The logo must be an image file.',
            'logo.max' => 'The logo must not be larger than 2MB.'
        ];
    }
}

