<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::OWNER, UserRole::MANAGER) ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', Rule::in([
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ])],
        ];
    }
}
