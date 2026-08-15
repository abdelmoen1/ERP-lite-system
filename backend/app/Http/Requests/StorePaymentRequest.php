<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_id' => [
                'required',
                'exists:debts,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'method' => [
                'nullable',
                'string',
                Rule::in(['cash']),
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'debt_id.required' => 'يرجى تحديد الدين أولاً.',
            'debt_id.exists' => 'الدين المحدد غير موجود.',

            'amount.required' => 'يرجى إدخال مبلغ الدفعة.',
            'amount.numeric' => 'مبلغ الدفعة يجب أن يكون رقمًا.',
            'amount.min' => 'مبلغ الدفعة يجب أن يكون أكبر من صفر.',

            'method.in' => 'طريقة الدفع المحددة غير صحيحة.',

            'paid_at.date' => 'تاريخ الدفع غير صحيح.',

            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}
