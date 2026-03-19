<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvisorCazadorAccessRequest extends FormRequest
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
        $advisor = $this->route('advisor');

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('advisors', 'username')->ignore($advisor),
            ],
            'pin' => ['nullable', 'digits:6', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $pin = $this->input('pin');
        if ($pin === '' || $pin === null) {
            $this->merge([
                'pin' => null,
                'pin_confirmation' => null,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'El usuario de acceso es obligatorio.',
            'username.unique' => 'Ese usuario ya está en uso por otro vendedor.',
            'pin.digits' => 'El PIN debe tener exactamente 6 dígitos.',
            'pin.confirmed' => 'La confirmación del PIN no coincide.',
        ];
    }
}
