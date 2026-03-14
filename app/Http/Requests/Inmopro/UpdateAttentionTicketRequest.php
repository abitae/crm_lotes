<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttentionTicketRequest extends FormRequest
{
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
            'status' => ['sometimes', 'string', 'in:pendiente,agendado,realizado,cancelado'],
            'scheduled_at' => ['nullable', 'date', Rule::requiredIf(fn (): bool => $this->input('status') === 'agendado')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
