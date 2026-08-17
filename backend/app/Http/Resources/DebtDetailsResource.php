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

            'invoice' => [
                'id' => $this->invoice->id,

                'customer' => new CustomerResource(
                    $this->invoice->customer
                ),

                'total_amount' => (float) $this->invoice->total_amount,

                'items' => InvoiceItemResource::collection(
                    $this->invoice->items
                ),
            ],
        ];
    }
}
