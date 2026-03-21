<?php

namespace App\Http\Requests\Api\v1\Cazador;

use App\Models\Inmopro\Datero;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDateroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('pin') === '' || $this->input('pin') === null) {
            $this->merge(['pin' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Datero $datero */
        $datero = $this->route('datero');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'city_id' => ['required', Rule::exists('cities', 'id')->where('is_active', true)],
            'dni' => ['required', 'string', 'max:20', Rule::unique('dateros', 'dni')->ignore($datero->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('dateros', 'username')->ignore($datero->id), Rule::unique('advisors', 'username')],
            'pin' => ['nullable', 'digits:6'],
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
