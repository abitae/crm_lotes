<?php

namespace App\Http\Requests\Api\v1\Cazador;

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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'phone' => 'teléfono',
            'email' => 'correo',
            'city_id' => 'ciudad',
            'dni' => 'DNI',
            'username' => 'usuario',
            'pin' => 'PIN',
            'is_active' => 'activo',
        ];
    }
}
