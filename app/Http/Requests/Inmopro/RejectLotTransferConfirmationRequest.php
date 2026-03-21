<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectLotTransferConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inmopro.lot-transfer-confirmations.reject') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Debe indicar el motivo del rechazo.',
        ];
    }
}
