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
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
