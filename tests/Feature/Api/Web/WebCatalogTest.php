<?php

namespace Tests\Feature\Api\Web;

use App\Models\Inmopro\Project;
use App\Models\Inmopro\ProjectAsset;
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

    public function test_public_asset_response(): void
    {
        Storage::fake('local');

        $project = Project::query()->firstOrFail();
        $storedPath = UploadedFile::fake()->image('plan.png')->store("projects/{$project->id}/images", 'local');
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

        $this->get(route('api.v1.web.projects.assets.show', [$project, $asset]))
            ->assertOk();
    }
}
