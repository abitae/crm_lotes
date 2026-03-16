<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLotRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_limit_date' => $this->normalizeDateInput($this->input('payment_limit_date')),
            'contract_date' => $this->normalizeDateInput($this->input('contract_date')),
            'notarial_transfer_date' => $this->normalizeDateInput($this->input('notarial_transfer_date')),
        ]);
    }

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
            'lot_status_id' => ['required', 'exists:lot_statuses,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'advisor_id' => ['nullable', 'exists:advisors,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_dni' => ['nullable', 'string', 'max:20'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'remaining_balance' => ['nullable', 'numeric', 'min:0'],
            'payment_limit_date' => ['nullable', 'date_format:Y-m-d'],
            'operation_number' => ['nullable', 'string', 'max:50'],
            'contract_date' => ['nullable', 'date_format:Y-m-d'],
            'contract_number' => ['nullable', 'string', 'max:50'],
            'notarial_transfer_date' => ['nullable', 'date_format:Y-m-d'],
            'observations' => ['nullable', 'string', 'max:1000'],
            'block' => ['sometimes', 'string', 'max:10'],
            'number' => ['sometimes', 'integer', 'min:1'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function normalizeDateInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) === 1) {
            return $normalized;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $normalized, $matches) === 1) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        $timestamp = strtotime($normalized);

        if ($timestamp === false) {
            return $value;
        }

        return date('Y-m-d', $timestamp);
    }
}
