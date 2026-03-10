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
        $projects = Project::withCount('lots')->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('inmopro/projects/index', [
            'projects' => $projects,
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
