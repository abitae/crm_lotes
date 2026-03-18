<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignMembershipRequest extends FormRequest
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
            'advisor_ids' => ['required', 'array', 'min:1'],
            'advisor_ids.*' => ['required', 'integer', 'exists:advisors,id'],
            'start_date' => ['required', 'date'],
        ];
    }
}
