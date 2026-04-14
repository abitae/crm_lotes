<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Team;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAdvisorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(AdvisorSeeder::class);
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
        $response->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('advisors')->has('teams')->has('cities')->has('materialTypes'));
    }

    public function test_authenticated_users_can_create_advisor(): void
    {
        $user = User::factory()->create();
        $level = AdvisorLevel::first();
        $team = Team::first();
        $city = City::firstOrFail();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.advisors.store'), [
            'dni' => '87654321',
            'first_name' => 'Asesor Nuevo',
            'last_name' => 'Test',
            'phone' => '999888777',
            'email' => 'asesor@example.com',
            'city_id' => $city->id,
            'team_id' => $team->id,
            'advisor_level_id' => $level->id,
            'personal_quota' => 10,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'dni' => '87654321',
            'first_name' => 'Asesor Nuevo',
            'last_name' => 'Test',
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
            'dni' => $advisor->dni,
            'first_name' => 'Asesor',
            'last_name' => 'Actualizado',
            'phone' => $advisor->phone,
            'email' => $advisor->email,
            'city_id' => $advisor->city_id ?? City::firstOrFail()->id,
            'team_id' => $advisor->team_id,
            'advisor_level_id' => $advisor->advisor_level_id,
            'personal_quota' => 15,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'id' => $advisor->id,
            'first_name' => 'Asesor',
            'last_name' => 'Actualizado',
            'name' => 'Asesor Actualizado',
            'personal_quota' => 15,
            'username' => $advisor->username,
        ]);
    }

    public function test_store_advisor_rejects_invalid_cci(): void
    {
        $user = User::factory()->create();
        $level = AdvisorLevel::first();
        $team = Team::first();
        $city = City::firstOrFail();
        $this->actingAs($user);

        $this->post(route('inmopro.advisors.store'), [
            'dni' => '87654322',
            'first_name' => 'Test',
            'last_name' => 'CCI',
            'phone' => '999888777',
            'email' => 'cci-invalid@example.com',
            'city_id' => $city->id,
            'team_id' => $team->id,
            'advisor_level_id' => $level->id,
            'personal_quota' => 10,
            'bank_cci' => '123',
        ])->assertSessionHasErrors(['bank_cci']);
    }
}
