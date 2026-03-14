<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproCitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
    }

    public function test_authenticated_users_can_visit_cities_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.cities.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/cities/index')->has('cities'));
    }

    public function test_authenticated_users_can_create_city(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('inmopro.cities.store'), [
                'name' => 'Huancayo',
                'code' => 'HYO',
                'department' => 'Junin',
                'sort_order' => 7,
                'is_active' => true,
            ])
            ->assertRedirect(route('inmopro.cities.index'));

        $this->assertDatabaseHas('cities', [
            'name' => 'Huancayo',
            'code' => 'HYO',
        ]);
    }

    public function test_authenticated_users_can_update_city(): void
    {
        $user = User::factory()->create();
        $city = City::first();

        $this->actingAs($user)
            ->put(route('inmopro.cities.update', $city), [
                'name' => 'Ciudad Actualizada',
                'code' => $city->code,
                'department' => 'Junin',
                'sort_order' => 3,
                'is_active' => false,
            ])
            ->assertRedirect(route('inmopro.cities.index'));

        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'name' => 'Ciudad Actualizada',
            'is_active' => false,
        ]);
    }
}
