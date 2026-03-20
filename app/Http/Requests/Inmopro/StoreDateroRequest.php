<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDateroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'advisor_id' => ['required', 'exists:advisors,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'city_id' => ['required', Rule::exists('cities', 'id')->where('is_active', true)],
            'dni' => ['required', 'string', 'max:20', 'unique:dateros,dni'],
            'username' => ['required', 'string', 'max:255', Rule::unique('dateros', 'username'), Rule::unique('advisors', 'username')],
            'pin' => ['required', 'digits:6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
