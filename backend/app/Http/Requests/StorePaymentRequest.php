<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'debt_id' => 'required|exists:debts,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|in:cash,bank_transfer,card,cheque,other',
            'paid_at' => 'required|date|before_or_equal:now',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'debt_id.required' => 'يجب اختيار الدين',
            'debt_id.exists' => 'الدين المحدد غير موجود في النظام',
            'amount.required' => 'يجب إدخال قيمة الدفعة',
            'amount.numeric' => 'يجب أن تكون قيمة الدفعة رقمية',
            'amount.min' => 'يجب أن تكون قيمة الدفعة أكبر من صفر',
            'paid_at.required' => 'يجب تحديد تاريخ ووقت الدفع',
            'paid_at.before_or_equal' => 'لا يمكن أن يكون تاريخ الدفع بالمستقبل',
            'method.in' => 'طريقة الدفع غير صحيحة',
        ];
    }
}
