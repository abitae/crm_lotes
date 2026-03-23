<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name' => $this->display_name === '' ? null : $this->display_name,
            'tagline' => $this->tagline === '' ? null : $this->tagline,
            'primary_color' => $this->primary_color === '' ? null : $this->primary_color,
        ]);
    }

    /**
     * @return array<string, array<int, mixed|string>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'file', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'image', 'max:512'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ];
    }
}
