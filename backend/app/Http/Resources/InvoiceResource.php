<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'has_debt' => (bool) $this->has_debt,
            'payment_method' => $this->payment_method,
            'total_amount' => (float) $this->total_amount,
            'debt' => new DebtResource($this->whenLoaded('debt')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
