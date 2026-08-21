<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            UserRole::OWNER,
            UserRole::EMPLOYEE
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'sometimes',
                'integer',
                Rule::exists('customers', 'id')->where(
                    fn($query) =>
                    $query->where('store_id', $this->user()->store_id)
                ),
            ],

            'has_debt' => [
                'sometimes',
                'boolean',
            ],

            'payment_method' => [
                'nullable',
                'string',
                Rule::in([
                    'cash',
                    'jawwal_pay',
                    'palpay',
                    'bank_of_palestine',
                ]),
                'required_if:has_debt,false',
                'prohibited_if:has_debt,true',
            ],

            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'items.*.item_name' => [
                'required_with:items',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required_with:items',
                'integer',
                'min:1',
            ],

            'items.*.unit_price' => [
                'required_with:items',
                'numeric',
                'min:0.01',
            ],
        ];
    }
}
