<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipPaymentRequest;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipRequest;
use App\Models\Inmopro\AdvisorMembership;
use App\Services\Inmopro\MembershipReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(StoreAdvisorMembershipRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['year'] = (int) $validated['year'];

        AdvisorMembership::create($validated);

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

    public function update(Request $request, AdvisorMembership $advisor_membership): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $advisor_membership->update($validated);

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

    public function storePayment(StoreAdvisorMembershipPaymentRequest $request, AdvisorMembership $advisor_membership): RedirectResponse
    {
        $validated = $request->validated();
        $validated['paid_at'] = \Carbon\Carbon::parse($validated['paid_at']);

        if (! empty($validated['advisor_membership_installment_id'])) {
            $installment = $advisor_membership->installments()->find($validated['advisor_membership_installment_id']);
            if (! $installment) {
                return redirect()
                    ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
                    ->with('error', 'La cuota no pertenece a esta membresía.');
            }
        }

        app(MembershipReceivableService::class)->recordPayment($advisor_membership, $validated);

        return redirect()
            ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
            ->with('success', 'Abono registrado correctamente.');
    }
}
