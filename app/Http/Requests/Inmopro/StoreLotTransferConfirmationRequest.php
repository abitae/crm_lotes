<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class StoreLotTransferConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirm-lot-transfer') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'evidence_image' => ['required', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evidence_image.required' => 'Debe adjuntar la evidencia de la transferencia.',
            'evidence_image.image' => 'La evidencia debe ser una imagen valida.',
            'evidence_image.max' => 'La imagen no debe superar los 5 MB.',
        ];
    }
}
