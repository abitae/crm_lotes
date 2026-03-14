<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvisorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('advisors', 'username')->ignore($this->route('advisor'))],
            'pin' => ['nullable', 'digits:6'],
            'is_active' => ['nullable', 'boolean'],
            'team_id' => ['required', 'exists:teams,id'],
            'advisor_level_id' => ['required', 'exists:advisor_levels,id'],
            'superior_id' => ['nullable', 'exists:advisors,id'],
            'personal_quota' => ['required', 'numeric', 'min:0'],
        ];
    }
}
