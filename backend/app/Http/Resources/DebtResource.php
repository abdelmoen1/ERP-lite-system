<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
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
            'invoice_id' => $this->invoice?->id,
            'source' => $this->invoice?->source?->value,
            'amount' => (float) $this->amount,
            'total_payments' => (float) $this->payments
                ->where('is_reversed', false)
                ->sum('amount'),
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
        ];
    }
}
