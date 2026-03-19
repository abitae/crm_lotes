<?php

namespace App\Http\Requests\Inmopro;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorMembershipRequest extends FormRequest
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
            'advisor_id' => ['required', 'exists:advisors,id'],
            'membership_type_id' => ['required', 'exists:membership_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }
}
