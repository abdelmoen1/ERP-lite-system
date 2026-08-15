<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'debt_id' => $this->debt_id,
            'customer' => $this->debt?->customer?->only(['id', 'name']),
            'amount' => $this->amount,
            'method' => $this->method,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'is_reversed' => $this->is_reversed,
            'reversed_at' => $this->reversed_at,
            'reversal_reason' => $this->reversal_reason,
            'debt_summary' => [
                'amount' => $this->debt->amount,
                'remaining_amount' => $this->debt->remaining_amount,
                'status' => $this->debt->status,
            ],
        ];
    }
}
