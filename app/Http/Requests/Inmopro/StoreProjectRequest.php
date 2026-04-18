<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'total_lots' => ['nullable', 'integer', 'min:0'],
            'blocks' => ['nullable', 'array'],
            'blocks.*' => ['string', 'max:10'],
            'image_files' => ['nullable', 'array'],
            'image_files.*' => ['file', 'image', 'max:10240'],
            'document_files' => ['nullable', 'array'],
            'document_files.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:15360'],
        ];
    }
}
