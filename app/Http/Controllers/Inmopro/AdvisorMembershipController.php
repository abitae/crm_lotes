<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipPaymentRequest;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorMembershipRequest;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\MembershipType;
use App\Services\Inmopro\MembershipReceivableService;
use Illuminate\Http\RedirectResponse;

class AdvisorMembershipController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'create_membership']);
    }

    public function store(StoreAdvisorMembershipRequest $request, MembershipReceivableService $receivableService): RedirectResponse
    {
        $validated = $request->validated();
        $type = MembershipType::findOrFail($validated['membership_type_id']);
        $amount = isset($validated['amount']) && $validated['amount'] !== '' && $validated['amount'] !== null
            ? (float) $validated['amount']
            : (float) $type->amount;
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $year = (int) $startDate->format('Y');

        $membership = AdvisorMembership::create([
            'advisor_id' => $validated['advisor_id'],
            'membership_type_id' => $validated['membership_type_id'],
            'year' => $year,
            'amount' => $amount,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $installmentsCount = isset($validated['installments_count']) ? (int) $validated['installments_count'] : 0;
        if ($installmentsCount > 0) {
            $receivableService->createInstallments($membership, $installmentsCount);
        }

        return redirect()
            ->route('inmopro.advisors.index')
            ->with('success', 'Membresía registrada correctamente.');
    }

    public function show(AdvisorMembership $advisor_membership): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', [
            'membership_id' => $advisor_membership->id,
        ]);
    }

    public function edit(AdvisorMembership $advisor_membership): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', [
            'membership_id' => $advisor_membership->id,
        ]);
    }

    public function update(UpdateAdvisorMembershipRequest $request, AdvisorMembership $advisor_membership): RedirectResponse
    {
        $validated = $request->validated();
        $data = [
            'amount' => $validated['amount'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ];
        if (array_key_exists('membership_type_id', $validated) && $validated['membership_type_id'] !== null) {
            $data['membership_type_id'] = $validated['membership_type_id'];
        }
        $advisor_membership->update($data);

        return redirect()
            ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
            ->with('success', 'Membresía actualizada.');
    }

    public function destroy(AdvisorMembership $advisor_membership): RedirectResponse
    {
        $advisor_membership->delete();

        return redirect()
            ->route('inmopro.advisors.index')
            ->with('success', 'Membresía eliminada.');
    }

    public function storePayment(StoreAdvisorMembershipPaymentRequest $request, AdvisorMembership $advisor_membership, MembershipReceivableService $receivableService): RedirectResponse
    {
        $validated = $request->validated();
        $validated['paid_at'] = \Carbon\Carbon::parse($validated['paid_at']);

        if (! empty($validated['advisor_membership_installment_id'])) {
            $installment = $advisor_membership->installments()->find($validated['advisor_membership_installment_id']);
            if (! $installment) {
                return redirect()
                    ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
                    ->withErrors(['advisor_membership_installment_id' => 'La cuota no pertenece a esta membresía.']);
            }
        }

        $receivableService->recordPayment($advisor_membership, $validated);

        return redirect()
            ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
            ->with('success', 'Abono registrado correctamente.');
    }
}
