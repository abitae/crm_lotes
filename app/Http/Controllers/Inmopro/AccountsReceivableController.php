<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipPaymentRequest;
use App\Http\Requests\Inmopro\StoreLotInstallmentRequest;
use App\Http\Requests\Inmopro\StoreLotPaymentRequest;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\CashAccount;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotInstallment;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Services\Inmopro\MembershipReceivableService;
use App\Services\Inmopro\ReceivableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountsReceivableController extends Controller
{
    public function __construct(
        private ReceivableService $receivableService
    ) {}

    public function index(Request $request): Response
    {
        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $query = Lot::with([
            'project',
            'client',
            'status',
            'installments',
            'payments.cashAccount',
        ])->when($statusLibre, fn ($builder) => $builder->where('lot_status_id', '!=', $statusLibre->id));

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('status')) {
            $requestedStatus = $request->input('status');
            $query->whereHas('installments', fn ($builder) => $builder->where('status', $requestedStatus));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($term) {
                $builder->where('block', 'like', "%{$term}%")
                    ->orWhere('number', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('dni', 'like', "%{$term}%"));
            });
        }

        $lots = $query
            ->orderByRaw('contract_date IS NULL')
            ->orderByDesc('contract_date')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();
        $lots->through(function (Lot $lot): array {
            $totalScheduled = (float) $lot->installments->sum('amount');
            $totalPaid = (float) $lot->payments->sum('amount');
            $overdue = $lot->installments->where('status', 'VENCIDA')->count();

            return [
                'id' => $lot->id,
                'block' => $lot->block,
                'number' => $lot->number,
                'price' => $lot->price,
                'advance' => $lot->advance,
                'remaining_balance' => $lot->remaining_balance,
                'project' => $lot->project,
                'client' => $lot->client,
                'status' => $lot->status,
                'contract_date' => $lot->contract_date,
                'installments' => $lot->installments,
                'payments' => $lot->payments,
                'total_scheduled' => $totalScheduled,
                'total_paid' => $totalPaid,
                'overdue_installments' => $overdue,
            ];
        });

        $portfolioLots = clone $query;
        $lotsCollection = $portfolioLots->get();
        $overdueInstallments = LotInstallment::query()->where('status', 'VENCIDA')->count();
        $totalScheduled = (float) $lotsCollection->sum(fn (Lot $lot) => $lot->installments->sum('amount'));
        $totalCollected = (float) $lotsCollection->sum(fn (Lot $lot) => $lot->payments->sum('amount'));

        $membershipReceivables = AdvisorMembership::query()
            ->with(['advisor', 'membershipType', 'installments', 'payments'])
            ->whereRaw('amount > COALESCE((SELECT SUM(amount) FROM advisor_membership_payments WHERE advisor_membership_id = advisor_memberships.id), 0)')
            ->orderBy('start_date')
            ->get()
            ->map(function (AdvisorMembership $m): array {
                $totalPaid = (float) $m->payments->sum('amount');
                $balanceDue = max(0, (float) $m->amount - $totalPaid);
                $overdue = $m->installments->where('status', 'VENCIDA')->count();

                return [
                    'id' => $m->id,
                    'advisor' => $m->advisor ? ['id' => $m->advisor->id, 'name' => $m->advisor->name, 'username' => $m->advisor->username] : null,
                    'membership_type' => $m->membershipType ? ['id' => $m->membershipType->id, 'name' => $m->membershipType->name] : null,
                    'start_date' => $m->start_date?->toISOString(),
                    'end_date' => $m->end_date?->toISOString(),
                    'amount' => (float) $m->amount,
                    'total_paid' => $totalPaid,
                    'balance_due' => $balanceDue,
                    'installments' => $m->installments->toArray(),
                    'payments' => $m->payments->toArray(),
                    'overdue_installments' => $overdue,
                ];
            });

        $membershipScheduled = $membershipReceivables->sum('amount');
        $membershipCollected = $membershipReceivables->sum('total_paid');
        $membershipPending = $membershipReceivables->sum('balance_due');
        $membershipOverdue = $membershipReceivables->sum('overdue_installments');

        return Inertia::render('inmopro/accounts-receivable', [
            'lots' => $lots,
            'projects' => Project::orderBy('name')->get(),
            'cashAccounts' => CashAccount::where('is_active', true)->orderBy('name')->get(),
            'membershipReceivables' => $membershipReceivables->values()->all(),
            'summary' => [
                'portfolio' => (float) $lotsCollection->sum('price'),
                'scheduled' => $totalScheduled,
                'collected' => $totalCollected,
                'pending' => max(0, $totalScheduled - $totalCollected),
                'overdueInstallments' => $overdueInstallments,
                'membershipScheduled' => $membershipScheduled,
                'membershipCollected' => $membershipCollected,
                'membershipPending' => $membershipPending,
                'membershipOverdueInstallments' => $membershipOverdue,
            ],
            'filters' => $request->only('project_id', 'status', 'search'),
        ]);
    }

    public function storeInstallment(StoreLotInstallmentRequest $request, Lot $lot): RedirectResponse
    {
        $this->receivableService->createInstallment($lot, $request->validated());

        return back()->with('success', 'Cuota registrada correctamente.');
    }

    public function storePayment(StoreLotPaymentRequest $request, Lot $lot): RedirectResponse
    {
        $this->receivableService->recordPayment($lot, $request->validated());

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function storeMembershipPayment(StoreAdvisorMembershipPaymentRequest $request): RedirectResponse
    {
        $request->validate(['membership_id' => ['required', 'integer', 'exists:advisor_memberships,id']]);
        $membership = AdvisorMembership::with('installments')->findOrFail($request->input('membership_id'));

        if ($request->filled('advisor_membership_installment_id')) {
            $installment = $membership->installments->firstWhere('id', $request->input('advisor_membership_installment_id'));
            if (! $installment) {
                return back()->with('error', 'La cuota no pertenece a esta membresía.');
            }
        }

        $validated = $request->validated();
        unset($validated['membership_id']);
        $validated['paid_at'] = \Carbon\Carbon::parse($validated['paid_at']);

        app(MembershipReceivableService::class)->recordPayment($membership, $validated);

        return back()->with('success', 'Pago de membresía registrado correctamente.');
    }
}
