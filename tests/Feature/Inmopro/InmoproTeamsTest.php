<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproTeamsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
    }

    public function test_authenticated_users_can_visit_teams_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.teams.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/teams/index')->has('teams'));
    }

    public function test_authenticated_users_can_create_team(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('inmopro.teams.store'), [
                'name' => 'Team Expansion',
                'code' => 'TEAM_EXPANSION',
                'description' => 'Equipo de expansion',
                'color' => '#123456',
                'sort_order' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('inmopro.teams.index'));

        $this->assertDatabaseHas('teams', [
            'name' => 'Team Expansion',
            'code' => 'TEAM_EXPANSION',
        ]);
    }

    public function test_authenticated_users_can_update_team(): void
    {
        $user = User::factory()->create();
        $team = Team::first();

        $this->actingAs($user)
            ->put(route('inmopro.teams.update', $team), [
                'name' => 'Team Actualizado',
                'code' => $team->code,
                'description' => 'Actualizado',
                'color' => '#654321',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('inmopro.teams.index'));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Team Actualizado',
        ]);
    }
}
