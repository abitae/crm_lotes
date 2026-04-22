<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorMaterialItemRequest extends FormRequest
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
        return [
            'advisor_material_type_id' => ['required', 'integer', 'exists:advisor_material_types,id'],
            'delivered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
