<?php

namespace App\Services\Inmopro;

use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\AdvisorMembershipInstallment;
use App\Models\Inmopro\AdvisorMembershipPayment;
use App\Models\Inmopro\CashAccount;
use App\Models\Inmopro\CashEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipReceivableService
{
    /**
     * Crea N cuotas para una membresía (monto total / N por cuota).
     */
    public function createInstallments(AdvisorMembership $membership, int $count): void
    {
        if ($count < 1) {
            return;
        }

        $total = (float) $membership->amount;
        $perInstallment = round($total / $count, 2);
        $startDate = $membership->start_date ? Carbon::parse($membership->start_date) : null;

        DB::transaction(function () use ($membership, $count, $perInstallment, $total, $startDate): void {
            for ($seq = 1; $seq <= $count; $seq++) {
                $dueDate = $startDate ? $startDate->copy()->addMonths($seq)->subDay() : null;
                $amount = $seq === $count
                    ? $total - ($perInstallment * ($count - 1))
                    : $perInstallment;

                $membership->installments()->create([
                    'sequence' => $seq,
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'PENDIENTE',
                ]);
            }
        });
    }

    /**
     * Registra un pago de membresía; opcionalmente asocia a una cuota y/o a una cuenta de caja.
     */
    public function recordPayment(AdvisorMembership $membership, array $validated): AdvisorMembershipPayment
    {
        return DB::transaction(function () use ($membership, $validated): AdvisorMembershipPayment {
            $payment = $membership->payments()->create([
                'advisor_membership_installment_id' => $validated['advisor_membership_installment_id'] ?? null,
                'cash_account_id' => $validated['cash_account_id'] ?? null,
                'amount' => $validated['amount'],
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['advisor_membership_installment_id'])) {
                $installment = AdvisorMembershipInstallment::query()
                    ->where('advisor_membership_id', $membership->id)
                    ->findOrFail($validated['advisor_membership_installment_id']);
                $installment->paid_amount = (float) $installment->paid_amount + (float) $validated['amount'];
                $this->refreshMembershipInstallmentStatus($installment);
                $installment->save();
            }

            if (! empty($validated['cash_account_id'])) {
                $cashAccount = CashAccount::findOrFail($validated['cash_account_id']);
                $cashAccount->increment('current_balance', (float) $validated['amount']);
                $advisorName = $membership->advisor?->name ?? 'Vendedor';
                CashEntry::create([
                    'cash_account_id' => $cashAccount->id,
                    'advisor_membership_payment_id' => $payment->id,
                    'type' => 'INGRESO',
                    'concept' => sprintf('Pago membresía - %s', $advisorName),
                    'amount' => $validated['amount'],
                    'entry_date' => $validated['paid_at'],
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            return $payment;
        });
    }

    public function refreshMembershipInstallmentStatus(AdvisorMembershipInstallment $installment): void
    {
        if ((float) $installment->paid_amount >= (float) $installment->amount) {
            $installment->status = 'PAGADA';

            return;
        }

        if ((float) $installment->paid_amount > 0) {
            $installment->status = 'PARCIAL';

            return;
        }

        if ($installment->due_date) {
            $installment->status = $installment->due_date->isPast() ? 'VENCIDA' : 'PENDIENTE';
        } else {
            $installment->status = 'PENDIENTE';
        }
    }
}
