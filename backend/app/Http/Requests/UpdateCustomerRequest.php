<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'name.max' => 'اسم العميل يجب ألا يزيد عن 255 حرف.',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص.',
            'address.string' => 'العنوان يجب أن يكون نص.',
        ];
    }
}
