<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('customers', 'phone')
                    ->where(fn($query) => $query->where('store_id', $this->user()->store_id))
                    ->ignore($this->route('customer')),
            ],
            'address' => 'sometimes|nullable|string|max:500',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'اسم العميل يجب ألا يزيد عن 255 حرف',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصًا',
            'phone.max' => 'رقم الهاتف طويل جدًا',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقًا لعميل آخر',
            'address.string' => 'العنوان يجب أن يكون نصًا',
        ];
    }
}
