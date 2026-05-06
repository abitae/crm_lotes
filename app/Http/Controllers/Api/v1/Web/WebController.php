<?php

namespace App\Http\Controllers\Api\v1\Web;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Models\Inmopro\ProjectAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebController extends Controller
{
    /**
     * Catálogo público de proyectos: totales globales, lotes y activos (imágenes / vídeos).
     */
    public function index(): JsonResponse
    {
        $projects = Project::query()
            ->with(['projectType'])
            ->with(['assets' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->withCount('lots')
            ->withCount([
                'lots as free_lots_count' => fn (Builder $b) => $b->whereHas(
                    'status',
                    fn (Builder $s) => $s->where('code', LotStatus::CODE_LIBRE)
                ),
            ])
            ->orderBy('name')
            ->get();

        $data = $projects->map(fn (Project $project) => $this->projectPayload($project))->all();

        return response()->json([
            'summary' => $this->summaryPayload(),
            'data' => $data,
        ]);
    }

    /**
     * Detalle de un proyecto (misma forma que cada elemento en el listado).
     */
    public function show(Project $project): JsonResponse
    {
        $project->load(['projectType']);
        $project->load(['assets' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')]);
        $project->loadCount('lots');
        $project->loadCount([
            'lots as free_lots_count' => fn (Builder $b) => $b->whereHas(
                'status',
                fn (Builder $s) => $s->where('code', LotStatus::CODE_LIBRE)
            ),
        ]);

        return response()->json([
            'data' => $this->projectPayload($project),
        ]);
    }

    /**
     * Sirve un archivo de proyecto (imagen, vídeo o documento) para usar las URLs públicas del JSON.
     */
    public function asset(Project $project, ProjectAsset $asset): StreamedResponse
    {
        abort_unless($asset->project_id === $project->id && $asset->is_active, 404);

        if (! Storage::disk('local')->exists($asset->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($asset->file_path, $asset->file_name, [
            'Content-Type' => $asset->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function summaryPayload(): array
    {
        $lotsTotal = Lot::query()->count();
        $lotsFree = Lot::query()
            ->whereHas('status', fn (Builder $s) => $s->where('code', LotStatus::CODE_LIBRE))
            ->count();

        $imagesTotal = ProjectAsset::query()
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->where('kind', 'image')
                    ->orWhere('mime_type', 'like', 'image/%');
            })
            ->count();

        $videosTotal = ProjectAsset::query()
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->where('kind', 'video')
                    ->orWhere('mime_type', 'like', 'video/%');
            })
            ->count();

        return [
            'projects_count' => Project::query()->count(),
            'lots_total' => $lotsTotal,
            'lots_free' => $lotsFree,
            'images_total' => $imagesTotal,
            'videos_total' => $videosTotal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projectPayload(Project $project): array
    {
        $assets = $project->relationLoaded('assets') ? $project->assets : collect();

        $images = $assets->filter(fn (ProjectAsset $a) => $this->assetIsImage($a))->values();
        $videos = $assets->filter(fn (ProjectAsset $a) => $this->assetIsVideo($a))->values();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'location' => $project->location,
            'blocks' => $project->blocks,
            'total_lots' => $project->total_lots,
            'lots_count' => $project->lots_count,
            'free_lots_count' => $project->free_lots_count ?? 0,
            'project_type' => $project->projectType ? [
                'id' => $project->projectType->id,
                'name' => $project->projectType->name,
                'code' => $project->projectType->code,
            ] : null,
            'images' => $images->map(fn (ProjectAsset $a) => $this->assetPayload($project, $a))->all(),
            'videos' => $videos->map(fn (ProjectAsset $a) => $this->assetPayload($project, $a))->all(),
            'images_count' => $images->count(),
            'videos_count' => $videos->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(Project $project, ProjectAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'kind' => $asset->kind,
            'title' => $asset->title,
            'file_name' => $asset->file_name,
            'mime_type' => $asset->mime_type,
            'file_size' => $asset->file_size,
            'url' => route('api.v1.web.projects.assets.show', [$project, $asset]),
        ];
    }

    private function assetIsImage(ProjectAsset $asset): bool
    {
        if ($asset->kind === 'image') {
            return true;
        }

        $mime = (string) $asset->mime_type;

        return str_starts_with($mime, 'image/');
    }

    private function assetIsVideo(ProjectAsset $asset): bool
    {
        if ($asset->kind === 'video') {
            return true;
        }

        $mime = (string) $asset->mime_type;

        return str_starts_with($mime, 'video/');
    }
}
