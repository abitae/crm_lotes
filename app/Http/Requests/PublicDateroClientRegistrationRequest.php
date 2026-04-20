<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesDateroCapturedClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PublicDateroClientRegistrationRequest extends FormRequest
{
    use ValidatesDateroCapturedClient;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['city_id', 'dni', 'email', 'referred_by'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->dateroCapturedClientRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->withDateroCapturedClientDuplicateCheck($validator, null);
    }
}
