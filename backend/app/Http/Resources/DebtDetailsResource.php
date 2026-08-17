<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtDetailsResource extends JsonResource
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
            'amount' => (float) $this->amount,

            'total_payments' => (float) $this->payments->sum('amount'),

            'remaining_amount' => (float) $this->remaining_amount,

            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'invoice' => new InvoiceResource(
                $this->invoice->load('customer', 'items')
            ),
        ];
    }
}
