<?php

namespace App\Http\Requests;

class UpdatePaymentMethodRequest extends StorePaymentMethodRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Make all fields optional for update
        foreach ($rules as $key => &$rule) {
            if (is_string($rule)) {
                $rule = 'sometimes|' . $rule;
            } elseif (is_array($rule)) {
                array_unshift($rule, 'sometimes');
            }
        }

        return $rules;
    }
}
