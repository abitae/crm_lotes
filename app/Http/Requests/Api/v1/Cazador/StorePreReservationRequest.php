<?php

namespace App\Http\Requests\Api\v1\Cazador;

use Illuminate\Foundation\Http\FormRequest;

class StorePreReservationRequest extends FormRequest
{
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
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'lot_id' => ['required', 'exists:lots,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'voucher_image' => ['required', 'image', 'max:5120'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
