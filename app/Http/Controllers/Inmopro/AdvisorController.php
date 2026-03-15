<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorMembershipFromAdvisorRequest;
use App\Http\Requests\Inmopro\StoreAdvisorRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\MembershipType;
use App\Models\Inmopro\Team;
use App\Services\Inmopro\MembershipReceivableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdvisorController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $term = trim((string) $q);
        if ($term === '') {
            return response()->json([]);
        }
        $like = '%'.$term.'%';
        $advisors = Advisor::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json($advisors);
    }

    public function index(Request $request): Response
    {
        $query = Advisor::with(['level', 'superior', 'team', 'memberships.payments', 'memberships.installments'])->withCount('lots');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $advisors = $query->orderBy('name')->paginate(20)->withQueryString();
        $advisorLevels = AdvisorLevel::orderBy('sort_order')->get();
        $advisorsList = Advisor::orderBy('name')->get(['id', 'name']);
        $teams = Team::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']);
        $membershipTypes = MembershipType::orderBy('name')->get(['id', 'name', 'months', 'amount']);

        $membershipDetail = null;
        if ($request->filled('membership_id')) {
            $m = AdvisorMembership::with(['advisor', 'payments', 'installments'])->find($request->input('membership_id'));
            if ($m) {
                $membershipDetail = [
                    'membership' => $m,
                    'totalPaid' => $m->totalPaid(),
                    'balanceDue' => $m->balanceDue(),
                    'isPaid' => $m->isPaid(),
                ];
            }
        }

        $advisorForModal = null;
        if ($request->filled('modal') && $request->input('modal') === 'edit_advisor' && $request->filled('advisor_id')) {
            $advisorForModal = Advisor::with('level')->find($request->input('advisor_id'));
        }

        $cashAccounts = \App\Models\Inmopro\CashAccount::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('inmopro/advisors/index', [
            'advisors' => $advisors,
            'advisorLevels' => $advisorLevels,
            'advisorsList' => $advisorsList,
            'teams' => $teams,
            'membershipTypes' => $membershipTypes,
            'cashAccounts' => $cashAccounts,
            'membershipDetail' => $membershipDetail,
            'advisorForModal' => $advisorForModal,
            'openModal' => $request->input('modal'),
            'filters' => $request->only('search'),
        ]);
    }

    public function storeMembership(StoreAdvisorMembershipFromAdvisorRequest $request, Advisor $advisor): RedirectResponse
    {
        $membershipReceivableService = app(MembershipReceivableService::class);
        $type = MembershipType::findOrFail($request->input('membership_type_id'));

        $alreadyAssigned = AdvisorMembership::query()
            ->where('advisor_id', $advisor->id)
            ->where('membership_type_id', $type->id)
            ->exists();
        if ($alreadyAssigned) {
            return redirect()
                ->route('inmopro.advisors.index')
                ->with('error', 'Este vendedor ya tiene asignada esta membresía.');
        }

        $startDate = \Carbon\Carbon::parse($request->input('start_date'));
        $endDate = $startDate->copy()->addMonths((int) $type->months)->subDay();
        $year = (int) $startDate->format('Y');
        $installmentsCount = (int) $request->input('installments_count', 1);

        $membership = AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'amount' => $type->amount,
        ]);

        if ($installmentsCount > 1) {
            $membershipReceivableService->createInstallments($membership, $installmentsCount);
        }

        return redirect()
            ->route('inmopro.advisors.index', ['membership_id' => $membership->id])
            ->with('success', 'Membresía asignada correctamente.');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'create_advisor']);
    }

    public function store(StoreAdvisorRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = $payload['is_active'] ?? true;

        Advisor::create($payload);

        return redirect()->route('inmopro.advisors.index')->with('success', 'Vendedor registrado correctamente.');
    }

    public function show(Advisor $advisor): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'edit_advisor', 'advisor_id' => $advisor->id]);
    }

    public function edit(Advisor $advisor): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'edit_advisor', 'advisor_id' => $advisor->id]);
    }

    public function update(UpdateAdvisorRequest $request, Advisor $advisor): RedirectResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = $payload['is_active'] ?? $advisor->is_active ?? true;

        $advisor->update($payload);

        return redirect()->route('inmopro.advisors.index');
    }

    public function resetPin(Advisor $advisor): RedirectResponse
    {
        $advisor->update([
            'pin' => '123456',
        ]);

        return redirect()
            ->route('inmopro.advisors.index')
            ->with('success', "PIN de {$advisor->name} restablecido a 123456.");
    }
}
