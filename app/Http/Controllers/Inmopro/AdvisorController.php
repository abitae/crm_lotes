<?php

namespace App\Http\Controllers\Inmopro;

use App\Exports\Inmopro\AdvisorsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ImportAdvisorConfirmRequest;
use App\Http\Requests\Inmopro\ImportAdvisorPreviewRequest;
use App\Http\Requests\Inmopro\StoreAdvisorRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorCazadorAccessRequest;
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
use Carbon\Carbon;
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

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

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
                ->route('inmopro.advisors.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inmopro.advisors.index')
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
            'materialItems.type',
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
            $advisorForModal = Advisor::with(['level', 'city', 'materialItems.type'])->find($request->input('advisor_id'));
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
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inmopro.advisors.index', ['modal' => 'create_advisor']);
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

        return redirect()->route('inmopro.advisors.index');
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
            ->route('inmopro.advisors.index')
            ->with('success', 'Usuario y acceso Cazador actualizados. Si cambió el PIN o el usuario, el vendedor debe iniciar sesión de nuevo en la app.');
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

            AdvisorMaterialItem::query()->updateOrCreate(
                [
                    'advisor_id' => $advisor->id,
                    'advisor_material_type_id' => (int) $row['advisor_material_type_id'],
                ],
                [
                    'delivered_at' => $deliveredAt,
                    'notes' => isset($row['notes']) && is_string($row['notes']) && $row['notes'] !== '' ? $row['notes'] : null,
                ]
            );
        }
    }
}
