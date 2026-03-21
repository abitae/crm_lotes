<?php

namespace App\Http\Requests\Api\v1\Datero;

use App\Models\Inmopro\Client;
use App\Services\Inmopro\ClientDuplicateRegistrationChecker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'dni' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $client = $this->route('client');
            $exceptId = $client instanceof Client ? $client->id : null;

            $checker = app(ClientDuplicateRegistrationChecker::class);
            $conflict = $checker->findConflict(
                $this->input('dni'),
                $this->input('phone'),
                $exceptId,
            );

            if ($conflict !== null) {
                $validator->errors()->add('duplicate_registration', $checker->message($conflict));
            }
        });
    }
}
