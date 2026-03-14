<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Project;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::query()
            ->withCount('lots')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'total_lots' => $project->total_lots,
                'lots_count' => $project->lots_count,
            ])->all(),
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        $project->loadCount('lots');

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'total_lots' => $project->total_lots,
                'lots_count' => $project->lots_count,
                'blocks' => $project->blocks,
            ],
        ]);
    }
}
