<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            UserRole::OWNER,
            UserRole::MANAGER
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')
                    ->where(
                        fn($query) =>
                        $query->where('store_id', $this->user()->store_id)
                    ),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل.',
            'customer_id.exists' => 'العميل غير موجود في متجرك.',
            'amount.required' => 'يجب إدخال قيمة الدين.',
            'amount.numeric' => 'قيمة الدين يجب أن تكون رقمية.',
            'amount.min' => 'قيمة الدين يجب أن تكون أكبر من صفر.',
        ];
    }
}
