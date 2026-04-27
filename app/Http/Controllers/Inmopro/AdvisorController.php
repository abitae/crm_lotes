<?php

namespace App\Http\Controllers\Inmopro;

use App\Exports\Inmopro\AdvisorsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ImportAdvisorConfirmRequest;
use App\Http\Requests\Inmopro\ImportAdvisorPreviewRequest;
use App\Http\Requests\Inmopro\StoreAdvisorMaterialItemRequest;
use App\Http\Requests\Inmopro\StoreAdvisorRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorCazadorAccessRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorMaterialItemsRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\AdvisorMaterialItem;
use App\Models\Inmopro\AdvisorMaterialType;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\City;
use App\Models\Inmopro\MembershipType;
use App\Models\Inmopro\Team;
use App\Services\Inmopro\AdvisorsExcelImportService;
use App\Support\InertiaListingRedirect;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdvisorController extends Controller
{
    public function excelTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new AdvisorsExport(collect(), true),
            'plantilla_vendedores.xlsx'
        );
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $query = Advisor::query()->with(['level', 'superior', 'team', 'city']);
        $this->applyAdvisorListFilters($query, $request);

        $advisors = $query->orderBy('name')->get();

        return Excel::download(
            new AdvisorsExport($advisors),
            'vendedores.xlsx'
        );
    }

    public function importPreview(ImportAdvisorPreviewRequest $request, AdvisorsExcelImportService $importService): JsonResponse
    {
        return response()->json($importService->preview($request->file('file')));
    }

    public function importConfirm(ImportAdvisorConfirmRequest $request, AdvisorsExcelImportService $importService): RedirectResponse
    {
        try {
            $importService->confirm($request->validated('token'), $request->user());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))
            ->with('success', 'Importación de vendedores completada correctamente.');
    }

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
            'level', 'superior', 'team', 'city',
            'memberships.membershipType',
            'memberships.installments',
            'memberships.payments',
            'materialItems' => fn ($q) => $q->orderByDesc('delivered_at')->orderByDesc('id'),
            'materialItems.type',
        ])->withCount('lots');

        $this->applyAdvisorListFilters($query, $request);

        $advisors = $query->orderBy('name')->paginate(20)->withQueryString();
        $advisors->getCollection()->transform(function (Advisor $advisor): Advisor {
            if (! $advisor->relationLoaded('memberships')) {
                return $advisor;
            }

            $advisor->setRelation('memberships', $advisor->memberships->values()->map(function (AdvisorMembership $membership): AdvisorMembership {
                if ($membership->relationLoaded('installments')) {
                    $membership->setRelation('installments', $membership->installments->values());
                }
                if ($membership->relationLoaded('payments')) {
                    $membership->setRelation('payments', $membership->payments->values());
                }

                return $membership;
            }));

            return $advisor;
        });
        $advisorLevels = AdvisorLevel::orderBy('sort_order')->get();
        $advisorsList = Advisor::orderBy('name')->get(['id', 'name']);
        $teams = Team::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']);
        $membershipTypes = MembershipType::orderBy('name')->get(['id', 'name', 'months', 'amount']);
        $materialTypes = AdvisorMaterialType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'department']);

        $membershipDetail = null;
        if ($request->filled('membership_id')) {
            $m = AdvisorMembership::with(['advisor', 'membershipType', 'installments', 'payments'])->find($request->input('membership_id'));
            if ($m) {
                $m->setRelation('installments', $m->installments->values());
                $m->setRelation('payments', $m->payments->values());
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
            $advisorForModal = Advisor::with([
                'level',
                'city',
                'materialItems' => fn ($q) => $q->orderByDesc('delivered_at')->orderByDesc('id'),
                'materialItems.type',
            ])->find($request->input('advisor_id'));
        }

        return Inertia::render('inmopro/advisors/index', [
            'advisors' => $advisors,
            'advisorLevels' => $advisorLevels,
            'advisorsList' => $advisorsList,
            'teams' => $teams,
            'cities' => $cities,
            'membershipTypes' => $membershipTypes,
            'materialTypes' => $materialTypes,
            'membershipDetail' => $membershipDetail,
            'advisorForModal' => $advisorForModal,
            'openModal' => $request->input('modal'),
            'filters' => $request->only('search', 'advisor_level_id', 'team_id', 'membership_pending'),
        ]);
    }

    /**
     * Filtros compartidos entre el listado Inertia y la exportación Excel.
     */
    private function applyAdvisorListFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('advisor_level_id')) {
            $query->where('advisor_level_id', $request->integer('advisor_level_id'));
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        if ($request->boolean('membership_pending')) {
            $query->whereRaw(
                'EXISTS (
                    SELECT 1 FROM advisor_memberships am
                    LEFT JOIN membership_types mt ON mt.id = am.membership_type_id
                    WHERE am.advisor_id = advisors.id
                    AND (am.membership_type_id IS NULL OR mt.months = 12)
                    AND am.year = (
                        SELECT MAX(am2.year) FROM advisor_memberships am2
                        LEFT JOIN membership_types mt2 ON mt2.id = am2.membership_type_id
                        WHERE am2.advisor_id = advisors.id
                        AND (am2.membership_type_id IS NULL OR mt2.months = 12)
                    )
                    AND COALESCE((
                        SELECT SUM(amp.amount) FROM advisor_membership_payments amp
                        WHERE amp.advisor_membership_id = am.id
                    ), 0) < am.amount - 0.0000001
                )'
            );
        }
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQueryMerged($request, [
            'modal' => 'create_advisor',
        ]));
    }

    public function store(StoreAdvisorRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $materialItems = $validated['material_items'] ?? null;
        unset($validated['material_items']);

        $validated['username'] = $validated['username'] ?? str((string) $validated['email'])->before('@')->slug('_')->value();
        $validated['pin'] = $validated['pin'] ?? '123456';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $advisor = Advisor::create($validated);
        $this->syncAdvisorMaterialItems($advisor, $materialItems);

        return redirect()->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))->with('success', 'Vendedor registrado correctamente.');
    }

    public function show(Request $request, Advisor $advisor): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQueryMerged($request, [
            'modal' => 'edit_advisor',
            'advisor_id' => $advisor->id,
        ]));
    }

    public function edit(Request $request, Advisor $advisor): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQueryMerged($request, [
            'modal' => 'edit_advisor',
            'advisor_id' => $advisor->id,
        ]));
    }

    public function update(UpdateAdvisorRequest $request, Advisor $advisor): RedirectResponse
    {
        $validated = $request->validated();
        $materialItems = $validated['material_items'] ?? null;
        unset($validated['material_items']);

        $validated['username'] = $validated['username'] ?? $advisor->username ?? str((string) ($validated['email'] ?? $advisor->email))->before('@')->slug('_')->value();
        $validated['is_active'] = $validated['is_active'] ?? $advisor->is_active ?? true;

        if (empty($validated['pin'])) {
            unset($validated['pin']);
        }

        $advisor->update($validated);
        $this->syncAdvisorMaterialItems($advisor->fresh(), $materialItems);

        return redirect()->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request));
    }

    public function updateCazadorAccess(UpdateAdvisorCazadorAccessRequest $request, Advisor $advisor): RedirectResponse
    {
        $validated = $request->validated();
        $usernameChanged = $advisor->username !== $validated['username'];
        $pinChanged = $request->filled('pin');

        $advisor->username = $validated['username'];

        if ($pinChanged) {
            $advisor->pin = $validated['pin'];
        }

        $advisor->save();

        if ($usernameChanged || $pinChanged) {
            $advisor->apiTokens()->delete();
        }

        return redirect()
            ->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))
            ->with('success', 'Usuario y acceso Cazador actualizados. Si cambió el PIN o el usuario, el vendedor debe iniciar sesión de nuevo en la app.');
    }

    public function updateMaterialItems(UpdateAdvisorMaterialItemsRequest $request, Advisor $advisor): RedirectResponse
    {
        $this->syncAdvisorMaterialItems($advisor, $request->validated('material_items'));

        return redirect()
            ->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))
            ->with('success', 'Materiales del vendedor actualizados.');
    }

    public function storeMaterialItem(StoreAdvisorMaterialItemRequest $request, Advisor $advisor): RedirectResponse
    {
        $validated = $request->validated();
        $deliveredRaw = $validated['delivered_at'] ?? null;
        $deliveredAt = null;
        if (is_string($deliveredRaw) && $deliveredRaw !== '') {
            $deliveredAt = Carbon::parse($deliveredRaw)->startOfDay();
        } else {
            $deliveredAt = Carbon::now()->startOfDay();
        }

        AdvisorMaterialItem::query()->create([
            'advisor_id' => $advisor->id,
            'advisor_material_type_id' => (int) $validated['advisor_material_type_id'],
            'delivered_at' => $deliveredAt,
            'notes' => isset($validated['notes']) && is_string($validated['notes']) && $validated['notes'] !== '' ? $validated['notes'] : null,
        ]);

        return redirect()
            ->route('inmopro.advisors.index', InertiaListingRedirect::advisorsIndexQuery($request))
            ->with('success', 'Entrega de material registrada.');
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     */
    private function syncAdvisorMaterialItems(Advisor $advisor, ?array $items): void
    {
        if ($items === null) {
            return;
        }

        foreach ($items as $row) {
            $deliveredRaw = $row['delivered_at'] ?? null;
            $deliveredAt = null;
            if (is_string($deliveredRaw) && $deliveredRaw !== '') {
                $deliveredAt = Carbon::parse($deliveredRaw);
            }

            $typeId = (int) $row['advisor_material_type_id'];
            $notes = isset($row['notes']) && is_string($row['notes']) && $row['notes'] !== '' ? $row['notes'] : null;

            $existing = AdvisorMaterialItem::query()
                ->where('advisor_id', $advisor->id)
                ->where('advisor_material_type_id', $typeId)
                ->orderByDesc('delivered_at')
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                $existing->update([
                    'delivered_at' => $deliveredAt,
                    'notes' => $notes,
                ]);
            } else {
                AdvisorMaterialItem::query()->create([
                    'advisor_id' => $advisor->id,
                    'advisor_material_type_id' => $typeId,
                    'delivered_at' => $deliveredAt,
                    'notes' => $notes,
                ]);
            }
        }
    }
}
