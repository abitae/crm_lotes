<?php

namespace App\Http\Controllers\Inmopro;

use App\Exports\Inmopro\ProjectWithLotsTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ImportProjectFromExcelRequest;
use App\Http\Requests\Inmopro\StoreProjectRequest;
use App\Http\Requests\Inmopro\UpdateProjectRequest;
use App\Imports\Inmopro\ProjectWithLotsImport;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->withCount('lots')
            ->withCount([
                'lots as free_lots_count' => fn (Builder $builder) => $builder->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('code', 'LIBRE')),
                'lots as pre_reserved_lots_count' => fn (Builder $builder) => $builder->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('code', 'PRERESERVA')),
                'lots as reserved_lots_count' => fn (Builder $builder) => $builder->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('code', 'RESERVADO')),
                'lots as transferred_lots_count' => fn (Builder $builder) => $builder->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('code', 'TRANSFERIDO')),
                'lots as installments_lots_count' => fn (Builder $builder) => $builder->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('code', 'CUOTAS')),
            ])
            ->withSum('lots as portfolio_value', 'price')
            ->withSum('lots as receivable_balance', 'remaining_balance');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        if ($request->filled('location')) {
            $query->where('location', (string) $request->input('location'));
        }

        if ($request->filled('health')) {
            match ((string) $request->input('health')) {
                'with_stock' => $query->whereHas('lots.status', fn (Builder $builder) => $builder->where('code', 'LIBRE')),
                'sold_out' => $query->whereDoesntHave('lots.status', fn (Builder $builder) => $builder->where('code', 'LIBRE')),
                'inconsistent' => $query->whereRaw('(select count(*) from lots where lots.project_id = projects.id) <> COALESCE(total_lots, 0)'),
                default => null,
            };
        }

        match ((string) $request->input('order')) {
            'lots_desc' => $query->orderByDesc('lots_count')->orderBy('name'),
            'balance_desc' => $query->orderByDesc('receivable_balance')->orderBy('name'),
            'value_desc' => $query->orderByDesc('portfolio_value')->orderBy('name'),
            'availability_desc' => $query->orderByDesc('free_lots_count')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        $projects = $query->paginate(15)->withQueryString()->through(function (Project $project): array {
            $plannedLots = $project->total_lots ?? 0;
            $actualLots = $project->lots_count ?? 0;
            $soldLots = ($project->reserved_lots_count ?? 0) + ($project->transferred_lots_count ?? 0) + ($project->installments_lots_count ?? 0);
            $occupancyRate = $actualLots > 0 ? round(($soldLots / $actualLots) * 100, 1) : 0.0;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'total_lots' => $project->total_lots,
                'blocks' => $project->blocks,
                'lots_count' => $actualLots,
                'free_lots_count' => $project->free_lots_count ?? 0,
                'pre_reserved_lots_count' => $project->pre_reserved_lots_count ?? 0,
                'reserved_lots_count' => $project->reserved_lots_count ?? 0,
                'transferred_lots_count' => $project->transferred_lots_count ?? 0,
                'installments_lots_count' => $project->installments_lots_count ?? 0,
                'portfolio_value' => (float) ($project->portfolio_value ?? 0),
                'receivable_balance' => (float) ($project->receivable_balance ?? 0),
                'occupancy_rate' => $occupancyRate,
                'consistency_gap' => $plannedLots - $actualLots,
                'is_consistent' => $plannedLots === $actualLots,
                'blocks_count' => count($project->blocks ?? []),
            ];
        });

        $projectCollection = $projects->getCollection();

        return Inertia::render('inmopro/projects/index', [
            'projects' => $projects,
            'filters' => [
                'search' => $request->input('search'),
                'location' => $request->input('location'),
                'health' => $request->input('health'),
                'order' => $request->input('order'),
            ],
            'locations' => Project::query()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->orderBy('location')
                ->distinct()
                ->pluck('location'),
            'summary' => [
                'totalProjects' => $projects->total(),
                'totalLots' => $projectCollection->sum('lots_count'),
                'totalFreeLots' => $projectCollection->sum('free_lots_count'),
                'totalBalance' => round((float) $projectCollection->sum('receivable_balance'), 2),
                'inconsistentProjects' => $projectCollection->where('is_consistent', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inmopro/projects/create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return redirect()->route('inmopro.projects.index');
    }

    public function show(Project $project): Response
    {
        $project->load(['lots.status', 'lots.client', 'lots.advisor']);

        return Inertia::render('inmopro/projects/show', [
            'project' => $project,
            'lotStatuses' => LotStatus::orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('inmopro/projects/edit', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('inmopro.projects.index');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('inmopro.projects.index');
    }

    public function excelTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new ProjectWithLotsTemplateExport,
            'plantilla_proyecto_lotes.xlsx'
        );
    }

    public function importFromExcel(ImportProjectFromExcelRequest $request): RedirectResponse
    {
        $import = new ProjectWithLotsImport;
        Excel::import($import, $request->file('file'));

        $project = $import->getProject();
        if ($project) {
            return redirect()
                ->route('inmopro.projects.show', $project)
                ->with('success', 'Proyecto y lotes importados correctamente.');
        }

        return redirect()
            ->route('inmopro.projects.index')
            ->with('error', 'No se pudo importar. Verifique que el archivo tenga el formato correcto y que exista el estado de lote LIBRE.');
    }
}
