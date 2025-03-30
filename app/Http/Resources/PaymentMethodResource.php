<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'is_default' => $this->is_default,
            'account_holder_name' => $this->name, // Assuming 'name' is the account holder name
        ];

        // MFS Payment Method Details
        if ($this->type === 'mfs' && $this->relationLoaded('paymentGateway')) {
            $data['payment_gateway'] = [
                'id' => $this->paymentGateway->id,
                'name' => $this->paymentGateway->name,
                'code' => $this->paymentGateway->code
            ];
            $data['account_number'] = $this->account_number;
        }

        // Card Payment Method Details
        if ($this->type === 'card') {
            $data['last_four'] = $this->last_four;
            $data['card_brand'] = $this->card_brand;
            $data['expiry_month'] = $this->expiry_month;
            $data['expiry_year'] = $this->expiry_year;
        }

        return $data;
    }
}
