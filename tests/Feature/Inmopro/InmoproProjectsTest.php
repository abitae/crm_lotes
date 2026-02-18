<?php

namespace Tests\Feature\Inmopro;

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
        $response->assertInertia(fn ($page) => $page->component('inmopro/projects/index')->has('projects'));
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
