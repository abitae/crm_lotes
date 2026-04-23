<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreProjectTypeRequest;
use App\Http\Requests\Inmopro\UpdateProjectTypeRequest;
use App\Models\Inmopro\ProjectType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $projectTypes = ProjectType::query()
            ->withCount('projects')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(function ($q) use ($request): void {
                    $term = trim((string) $request->input('search'));
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                })
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inmopro/project-types/index', [
            'projectTypes' => $projectTypes,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreProjectTypeRequest $request): RedirectResponse
    {
        ProjectType::create($request->validated());

        return redirect()->route('inmopro.project-types.index');
    }

    public function update(UpdateProjectTypeRequest $request, ProjectType $project_type): RedirectResponse
    {
        $project_type->update($request->validated());

        return redirect()->route('inmopro.project-types.index');
    }

    public function destroy(ProjectType $project_type): RedirectResponse
    {
        $project_type->delete();

        return redirect()->route('inmopro.project-types.index');
    }
}
