<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Models\Inmopro\ProjectAsset;
use App\Models\Inmopro\ProjectType;
use App\Models\User;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InmoproProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProjectSeeder::class);
        $this->seed(LotStatusSeeder::class);
    }

    public function test_guests_cannot_visit_projects_index(): void
    {
        $response = $this->get(route('inmopro.projects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_projects_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.projects.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/projects/index')
            ->has('projects')
            ->has('projectTypes')
            ->has('locations')
            ->has('summary')
            ->where('filters.search', null)
            ->where('filters.project_type_id', null));
    }

    public function test_authenticated_users_can_visit_project_show(): void
    {
        $user = User::factory()->create();
        $project = Project::firstOrFail();
        $freeStatus = LotStatus::where('code', 'LIBRE')->firstOrFail();

        Lot::create([
            'project_id' => $project->id,
            'block' => 'A',
            'number' => 2,
            'lot_status_id' => $freeStatus->id,
            'area' => 100,
            'price' => 25000,
        ]);

        $this->actingAs($user)
            ->get(route('inmopro.projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/projects/show')
                ->where('project.id', $project->id)
                ->has('lotStatuses'));
    }

    public function test_projects_index_can_filter_by_search_and_health(): void
    {
        $user = User::factory()->create();
        $project = Project::first();
        $freeStatus = LotStatus::where('code', 'LIBRE')->firstOrFail();

        Lot::create([
            'project_id' => $project->id,
            'block' => 'A',
            'number' => 1,
            'lot_status_id' => $freeStatus->id,
            'area' => 120,
            'price' => 30000,
            'remaining_balance' => 15000,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('inmopro.projects.index', [
            'search' => $project->name,
            'project_type_id' => $project->project_type_id,
            'health' => 'with_stock',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/projects/index')
            ->where('filters.search', $project->name)
            ->where('filters.project_type_id', (string) $project->project_type_id)
            ->where('filters.health', 'with_stock')
            ->has('projects.data', 1)
            ->where('projects.data.0.name', $project->name)
            ->where('projects.data.0.project_type.id', $project->project_type_id)
            ->where('projects.data.0.free_lots_count', 1));
    }

    public function test_authenticated_users_can_create_project(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $projectType = ProjectType::query()->firstOrFail();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.projects.store'), [
            'name' => 'Proyecto Test',
            'project_type_id' => $projectType->id,
            'location' => 'Lima',
            'total_lots' => 50,
            'blocks' => ['A', 'B'],
            'image_files' => [UploadedFile::fake()->image('hero.png')],
            'document_files' => [UploadedFile::fake()->create('brochure.pdf', 120, 'application/pdf')],
        ]);

        $response->assertRedirect(route('inmopro.projects.index'));
        $this->assertDatabaseHas('projects', [
            'name' => 'Proyecto Test',
            'project_type_id' => $projectType->id,
            'location' => 'Lima',
        ]);
        $project = Project::query()->where('name', 'Proyecto Test')->firstOrFail();
        $this->assertDatabaseHas('project_assets', [
            'project_id' => $project->id,
            'kind' => 'image',
        ]);
        $this->assertDatabaseHas('project_assets', [
            'project_id' => $project->id,
            'kind' => 'document',
        ]);
    }

    public function test_authenticated_users_can_update_project(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = Project::first();
        $projectType = ProjectType::query()->whereKeyNot($project?->project_type_id)->first() ?? ProjectType::query()->firstOrFail();
        $this->actingAs($user);

        $response = $this->put(route('inmopro.projects.update', $project), [
            'name' => 'Proyecto Actualizado',
            'project_type_id' => $projectType->id,
            'location' => $project->location,
            'total_lots' => $project->total_lots,
            'blocks' => $project->blocks ?? [],
            'document_files' => [UploadedFile::fake()->create('price-list.xlsx', 80, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
        ]);

        $response->assertRedirect(route('inmopro.projects.index'));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Proyecto Actualizado',
            'project_type_id' => $projectType->id,
        ]);
        $this->assertDatabaseHas('project_assets', [
            'project_id' => $project->id,
            'kind' => 'document',
        ]);
    }

    public function test_authenticated_users_can_download_and_delete_project_asset(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $project = Project::firstOrFail();
        $path = UploadedFile::fake()->create('memoria.pdf', 100, 'application/pdf')->store("projects/{$project->id}/documents", 'local');
        $asset = ProjectAsset::create([
            'project_id' => $project->id,
            'kind' => 'document',
            'title' => 'Memoria',
            'file_name' => 'memoria.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 100 * 1024,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('inmopro.projects.assets.download', [$project, $asset]))
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('inmopro.projects.assets.destroy', [$project, $asset]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('project_assets', ['id' => $asset->id]);
    }
}
