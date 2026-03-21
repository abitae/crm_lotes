<?php

namespace App\Http\Requests\Api\v1\Datero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginDateroRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
