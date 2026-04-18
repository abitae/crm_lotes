<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvisorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'dni' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', 'unique:advisors,dni'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'city_id' => ['required', Rule::exists('cities', 'id')->where('is_active', true)],
            'username' => ['nullable', 'string', 'max:255', 'unique:advisors,username'],
            'pin' => ['nullable', 'digits:6'],
            'is_active' => ['nullable', 'boolean'],
            'team_id' => ['required', 'exists:teams,id'],
            'advisor_level_id' => ['required', 'exists:advisor_levels,id'],
            'superior_id' => ['nullable', 'exists:advisors,id'],
            'personal_quota' => ['required', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_cci' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]{20}$/'],
            'material_items' => ['nullable', 'array'],
            'material_items.*.advisor_material_type_id' => ['required', 'integer', 'exists:advisor_material_types,id'],
            'material_items.*.delivered_at' => ['nullable', 'date'],
            'material_items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_cci.regex' => 'El CCI debe tener exactamente 20 dígitos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $dni = $this->input('dni');
        if (is_string($dni)) {
            $digits = preg_replace('/\D/', '', $dni);
            if (strlen($digits) === 8) {
                $this->merge(['dni' => $digits]);
            }
        }

        $cci = $this->input('bank_cci');
        if ($cci === '' || $cci === null) {
            $this->merge(['bank_cci' => null]);
        }

        $birth = $this->input('birth_date');
        if ($birth === '' || $birth === null) {
            $this->merge(['birth_date' => null]);
        }

        $joined = $this->input('joined_at');
        if ($joined === '' || $joined === null) {
            $this->merge(['joined_at' => null]);
        }
    }
}
