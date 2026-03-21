<?php

namespace App\Http\Requests\Inmopro\AccessControl;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessControlUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists(config('permission.table_names.roles'), 'id')->where('guard_name', 'web'),
            ],
        ];
    }
}
