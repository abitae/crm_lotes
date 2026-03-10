<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipPaymentRequest;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipRequest;
use App\Models\Inmopro\AdvisorMembership;
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
        $validated['advisor_membership_id'] = $advisor_membership->id;
        $validated['paid_at'] = \Carbon\Carbon::parse($validated['paid_at']);

        $advisor_membership->payments()->create($validated);

        return redirect()
            ->route('inmopro.advisors.index', ['membership_id' => $advisor_membership->id])
            ->with('success', 'Abono registrado correctamente.');
    }
}
