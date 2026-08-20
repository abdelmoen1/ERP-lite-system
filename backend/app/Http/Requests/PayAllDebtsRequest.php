<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayAllDebtsRequest extends FormRequest
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
            'method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'jawwal_pay',
                    'palpay',
                    'bank_of_palestine',
                ]),
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
            'method.required' => 'يرجى تحديد طريقة الدفع.',
            'method.in' => 'طريقة الدفع المحددة غير صحيحة.',
            'paid_at.date' => 'تاريخ الدفع غير صحيح.',
            'notes.string' => 'الملاحظات يجب أن تكون نصًا.',
        ];
    }
}
