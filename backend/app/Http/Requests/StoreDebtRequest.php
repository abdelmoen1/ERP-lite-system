<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreDebtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
        ];
    }
    public function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل',
            'customer_id.exists' => 'العميل المحدد غير موجود في النظام',
            'amount.required' => 'يجب ادخل قيمة الدين',
            'amount.numeric' => 'يجب أن تكون قيمة الدين رقمية',
            'amount.min' => 'يجب أن تكون قيمة الدين أكبر من صفر',
        ];
    }
}
