<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\MembershipType;
use App\Models\Inmopro\Team;
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
        $query = Advisor::with([
            'level', 'superior', 'team',
            'memberships.membershipType',
            'memberships.installments',
            'memberships.payments',
        ])->withCount('lots');

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
            $m = AdvisorMembership::with(['advisor', 'membershipType', 'installments', 'payments'])->find($request->input('membership_id'));
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

        return Inertia::render('inmopro/advisors/index', [
            'advisors' => $advisors,
            'advisorLevels' => $advisorLevels,
            'advisorsList' => $advisorsList,
            'teams' => $teams,
            'membershipTypes' => $membershipTypes,
            'membershipDetail' => $membershipDetail,
            'advisorForModal' => $advisorForModal,
            'openModal' => $request->input('modal'),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'create_advisor']);
    }

    public function store(StoreAdvisorRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['username'] = $payload['username'] ?? str((string) $payload['email'])->before('@')->slug('_')->value();
        $payload['pin'] = $payload['pin'] ?? '123456';
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
        $payload['username'] = $payload['username'] ?? $advisor->username ?? str((string) ($payload['email'] ?? $advisor->email))->before('@')->slug('_')->value();
        $payload['is_active'] = $payload['is_active'] ?? $advisor->is_active ?? true;

        if (empty($payload['pin'])) {
            unset($payload['pin']);
        }

        $advisor->update($payload);

        return redirect()->route('inmopro.advisors.index');
    }
}
