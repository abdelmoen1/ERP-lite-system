<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReversePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يجب كتابة سبب إلغاء الدفعة',
            'reason.max' => 'السبب طويل جدًا',
        ];
    }
}
