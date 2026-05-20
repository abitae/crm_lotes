<?php

namespace Tests\Feature\Api\Web;

use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Models\Inmopro\ProjectAsset;
use App\Models\Inmopro\ProjectType;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientSeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorSeeder::class);
        $this->seed(ClientSeeder::class);
        $this->seed(LotSeeder::class);
    }

    public function test_projects_catalog_is_public_and_returns_summary_and_data(): void
    {
        $response = $this->getJson(route('api.v1.web.projects.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'projects_count',
                    'lots_total',
                    'lots_free',
                    'images_total',
                    'videos_total',
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'location',
                        'blocks',
                        'total_lots',
                        'lots_count',
                        'free_lots_count',
                        'project_type',
                        'images',
                        'videos',
                        'images_count',
                        'videos_count',
                    ],
                ],
            ]);

        $this->assertSame(Project::query()->count(), $response->json('meta.total'));
    }

    public function test_projects_catalog_supports_pagination(): void
    {
        $response = $this->getJson(route('api.v1.web.projects.index', [
            'page' => 1,
            'per_page' => 2,
        ]));

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', Project::query()->count())
            ->assertJsonCount(2, 'data');
    }

    public function test_projects_catalog_filters_by_search(): void
    {
        $project = Project::query()->where('name', 'Mirador 3.1')->firstOrFail();

        $this->getJson(route('api.v1.web.projects.index', ['search' => 'Mirador']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $project->id);
    }

    public function test_projects_catalog_filters_by_project_type(): void
    {
        $type = ProjectType::query()->where('code', 'RESIDENCIAL')->firstOrFail();
        $expectedCount = Project::query()->where('project_type_id', $type->id)->count();

        $this->getJson(route('api.v1.web.projects.index', ['project_type_id' => $type->id]))
            ->assertOk()
            ->assertJsonPath('meta.total', $expectedCount);
    }

    public function test_projects_catalog_filters_projects_with_free_lots(): void
    {
        $expectedCount = Project::query()
            ->whereHas('lots', fn ($q) => $q->whereHas(
                'status',
                fn ($s) => $s->where('code', LotStatus::CODE_LIBRE)
            ))
            ->count();

        $this->getJson(route('api.v1.web.projects.index', ['has_free_lots' => 1]))
            ->assertOk()
            ->assertJsonPath('meta.total', $expectedCount);
    }

    public function test_projects_catalog_rejects_invalid_filters(): void
    {
        $this->getJson(route('api.v1.web.projects.index', [
            'per_page' => 100,
            'order' => 'invalid',
        ]))
            ->assertUnprocessable();
    }

    public function test_show_project_returns_payload(): void
    {
        $project = Project::query()->firstOrFail();

        $this->getJson(route('api.v1.web.projects.show', $project))
            ->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'location',
                    'total_lots',
                    'lots_count',
                    'free_lots_count',
                    'images',
                    'videos',
                ],
            ]);
    }

    public function test_catalog_returns_public_storage_urls_for_images(): void
    {
        Storage::fake('public');

        $project = Project::query()->firstOrFail();
        $storedPath = UploadedFile::fake()->image('plan.png')->store("projects/{$project->id}/images", 'public');
        ProjectAsset::create([
            'project_id' => $project->id,
            'kind' => 'image',
            'title' => 'Plan',
            'file_name' => 'plan.png',
            'file_path' => $storedPath,
            'mime_type' => 'image/png',
            'file_size' => 500,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('api.v1.web.projects.show', $project));

        $response->assertOk();
        $url = $response->json('data.images.0.url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/', $url);
        $this->assertStringContainsString($storedPath, $url);
    }

    public function test_public_asset_route_redirects_to_storage_url(): void
    {
        Storage::fake('public');

        $project = Project::query()->firstOrFail();
        $storedPath = UploadedFile::fake()->image('plan.png')->store("projects/{$project->id}/images", 'public');
        $asset = ProjectAsset::create([
            'project_id' => $project->id,
            'kind' => 'image',
            'title' => 'Plan',
            'file_name' => 'plan.png',
            'file_path' => $storedPath,
            'mime_type' => 'image/png',
            'file_size' => 500,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $expectedUrl = Storage::disk('public')->url($storedPath);

        $this->get(route('api.v1.web.projects.assets.show', [$project, $asset]))
            ->assertRedirect($expectedUrl);
    }
}
