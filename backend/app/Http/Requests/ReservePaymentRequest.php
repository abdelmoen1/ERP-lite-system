<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;

class ReservePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::OWNER) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'reason.required' => 'يرجى إدخال سبب إلغاء الدفعة.',
            'reason.string' => 'سبب الإلغاء يجب أن يكون نصًا.',
            'reason.max' => 'سبب الإلغاء طويل جدًا.',
        ];
    }
}
