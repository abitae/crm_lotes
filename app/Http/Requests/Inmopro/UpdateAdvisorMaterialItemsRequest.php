<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvisorMaterialItemsRequest extends FormRequest
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
            'material_items' => ['required', 'array'],
            'material_items.*.advisor_material_type_id' => ['required', 'integer', 'exists:advisor_material_types,id'],
            'material_items.*.delivered_at' => ['nullable', 'date'],
            'material_items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
