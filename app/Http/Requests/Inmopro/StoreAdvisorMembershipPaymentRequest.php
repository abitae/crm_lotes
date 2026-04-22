<?php

namespace App\Http\Requests\Inmopro;

use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\AdvisorMembershipInstallment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAdvisorMembershipPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Asignación automática: primera cuota con saldo pendiente (menor número de secuencia).
     * No se usa el valor enviado por el cliente para evitar manipulación.
     */
    protected function prepareForValidation(): void
    {
        $membership = $this->route('advisor_membership');
        if (! $membership instanceof AdvisorMembership) {
            return;
        }

        $firstPayable = $membership->installments()
            ->orderBy('sequence')
            ->get()
            ->first(static function (AdvisorMembershipInstallment $i): bool {
                return (float) $i->paid_amount < (float) $i->amount - 0.00001;
            });

        $this->merge([
            'advisor_membership_installment_id' => $firstPayable?->id,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $membership = $this->route('advisor_membership');
        $membershipId = $membership?->id ?? 0;

        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'advisor_membership_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('advisor_membership_installments', 'id')
                    ->where('advisor_membership_id', $membershipId),
            ],
            'cash_account_id' => ['nullable', 'exists:cash_accounts,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('advisor_membership_installment_id')) {
                $validator->errors()->add(
                    'amount',
                    'No hay cuotas con saldo pendiente. Añada una cuota antes de registrar el abono.'
                );

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $id = (int) $this->input('advisor_membership_installment_id');
            if ($id < 1) {
                return;
            }

            $installment = AdvisorMembershipInstallment::query()->find($id);
            if (! $installment) {
                return;
            }

            $amount = (float) $this->input('amount');
            $cap = max(0, (float) $installment->amount - (float) $installment->paid_amount);
            if ($amount > $cap + 0.00001) {
                $validator->errors()->add('amount', 'El monto excede el saldo pendiente de la cuota.');
            }
        });
    }
}
