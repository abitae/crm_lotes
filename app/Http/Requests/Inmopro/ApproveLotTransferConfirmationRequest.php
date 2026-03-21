<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLotTransferConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inmopro.lot-transfer-confirmations.approve') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'review_notes.required' => 'Debe ingresar una reseña antes de aprobar.',
        ];
    }
}
