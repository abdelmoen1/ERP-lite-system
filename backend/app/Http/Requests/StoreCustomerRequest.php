<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'address' => 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'اسم العميل مطلوب',
            'name.max' => 'اسم العميل لا يزيد عن 30 حرف',
            'phone.required' => 'رقم الهاتف مطلوب',
            'address.string' => 'العنوان يجب ان يكون نصا'
        ];      
    }
}
