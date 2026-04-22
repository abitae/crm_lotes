<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorMembershipInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('sequence') === '' || $this->input('sequence') === null) {
            $this->merge(['sequence' => null]);
        }
        if ($this->input('notes') === '') {
            $this->merge(['notes' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];
    }
}
