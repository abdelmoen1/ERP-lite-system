<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::OWNER, UserRole::MANAGER) ?? false;
    }

    public function rules(): array
    {
        $allowedRoles = $this->user()->hasRole(UserRole::OWNER)
            ? [UserRole::MANAGER->value, UserRole::EMPLOYEE->value]
            : [UserRole::EMPLOYEE->value];

        return [
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in($allowedRoles)],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ];
    }
}
