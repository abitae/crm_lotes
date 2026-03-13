<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreCashAccountRequest;
use App\Http\Requests\Inmopro\StoreCashEntryRequest;
use App\Models\Inmopro\CashAccount;
use App\Services\Inmopro\ReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashAccountController extends Controller
{
    public function __construct(
        private ReceivableService $receivableService
    ) {}

    public function index(Request $request): Response
    {
        $accounts = CashAccount::with(['entries', 'lotPayments'])
            ->orderBy('name')
            ->get()
            ->map(function (CashAccount $account): array {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'currency' => $account->currency,
                    'initial_balance' => $account->initial_balance,
                    'current_balance' => $account->current_balance,
                    'is_active' => $account->is_active,
                    'entries' => $account->entries()->latest('entry_date')->limit(15)->get(),
                    'payments_count' => $account->lotPayments()->count(),
                ];
            })
            ->values();

        return Inertia::render('inmopro/cash-accounts/index', [
            'accounts' => $accounts,
        ]);
    }

    public function store(StoreCashAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['current_balance'] = $validated['initial_balance'];

        CashAccount::create($validated);

        return back()->with('success', 'Cuenta registrada correctamente.');
    }

    public function storeEntry(StoreCashEntryRequest $request, CashAccount $cash_account): RedirectResponse
    {
        $this->receivableService->recordManualEntry($cash_account, $request->validated());

        return back()->with('success', 'Movimiento registrado correctamente.');
    }
}
