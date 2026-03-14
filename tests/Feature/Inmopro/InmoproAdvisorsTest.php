<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAdvisorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
    }

    public function test_guests_cannot_visit_advisors_index(): void
    {
        $response = $this->get(route('inmopro.advisors.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_advisors_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.advisors.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('advisors')->has('teams'));
    }

    public function test_authenticated_users_can_create_advisor(): void
    {
        $user = User::factory()->create();
        $level = AdvisorLevel::first();
        $team = Team::first();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.advisors.store'), [
            'name' => 'Asesor Nuevo Test',
            'phone' => '999888777',
            'email' => 'asesor@example.com',
            'team_id' => $team->id,
            'advisor_level_id' => $level->id,
            'personal_quota' => 10,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'name' => 'Asesor Nuevo Test',
            'email' => 'asesor@example.com',
            'team_id' => $team->id,
            'username' => 'asesor',
        ]);
    }

    public function test_authenticated_users_can_update_advisor(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $this->actingAs($user);

        $response = $this->put(route('inmopro.advisors.update', $advisor), [
            'name' => 'Asesor Actualizado',
            'phone' => $advisor->phone,
            'email' => $advisor->email,
            'team_id' => $advisor->team_id,
            'advisor_level_id' => $advisor->advisor_level_id,
            'personal_quota' => 15,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'id' => $advisor->id,
            'name' => 'Asesor Actualizado',
            'personal_quota' => 15,
            'username' => $advisor->username,
        ]);
    }
}
