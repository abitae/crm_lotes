<?php

namespace App\Http\Requests\Api\v1\Datero;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDateroPinRequest extends FormRequest
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
            'current_pin' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ];
    }
}
