<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpcomingBillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'biller' => $this->biller ? $this->biller->name : null,
            'amount' => number_format($this->amount, 2),
            'due_date' => $this->due_date->toDateString(),
            'category' => $this->category,
            'status' => $this->status,
            'is_recurring' => $this->is_recurring,
            'days_until_due' => now()->diffInDays($this->due_date)
        ];
    }
}
