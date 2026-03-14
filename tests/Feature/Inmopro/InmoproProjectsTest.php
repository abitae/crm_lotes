<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
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
            ->has('locations')
            ->has('summary')
            ->where('filters.search', null));
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
            'health' => 'with_stock',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/projects/index')
            ->where('filters.search', $project->name)
            ->where('filters.health', 'with_stock')
            ->has('projects.data', 1)
            ->where('projects.data.0.name', $project->name)
            ->where('projects.data.0.free_lots_count', 1));
    }

    public function test_authenticated_users_can_create_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.projects.store'), [
            'name' => 'Proyecto Test',
            'location' => 'Lima',
            'total_lots' => 50,
            'blocks' => ['A', 'B'],
        ]);

        $response->assertRedirect(route('inmopro.projects.index'));
        $this->assertDatabaseHas('projects', [
            'name' => 'Proyecto Test',
            'location' => 'Lima',
        ]);
    }

    public function test_authenticated_users_can_update_project(): void
    {
        $user = User::factory()->create();
        $project = Project::first();
        $this->actingAs($user);

        $response = $this->put(route('inmopro.projects.update', $project), [
            'name' => 'Proyecto Actualizado',
            'location' => $project->location,
            'total_lots' => $project->total_lots,
            'blocks' => $project->blocks ?? [],
        ]);

        $response->assertRedirect(route('inmopro.projects.index'));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Proyecto Actualizado',
        ]);
    }
}
