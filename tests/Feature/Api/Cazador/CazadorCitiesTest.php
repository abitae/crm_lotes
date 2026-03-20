<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorCitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_cities_requires_authentication(): void
    {
        $this->getJson(route('api.v1.cazador.cities.index'))
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'No autenticado.']);
    }

    public function test_advisor_can_list_active_cities(): void
    {
        $advisor = Advisor::firstOrFail();
        $token = $this->loginToken($advisor);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.cities.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'department', 'code'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(6, count($response->json('data')));
    }

    public function test_inactive_cities_are_not_listed(): void
    {
        City::create([
            'name' => 'Ciudad Oculta',
            'code' => 'HID',
            'department' => 'Test',
            'sort_order' => 99,
            'is_active' => false,
        ]);

        $advisor = Advisor::firstOrFail();
        $token = $this->loginToken($advisor);

        $names = collect($this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.cities.index'))
            ->json('data'))
            ->pluck('name')
            ->all();

        $this->assertNotContains('Ciudad Oculta', $names);
    }

    public function test_search_filters_by_name_or_department(): void
    {
        $advisor = Advisor::firstOrFail();
        $token = $this->loginToken($advisor);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.cities.index', ['search' => 'Lima']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Lima']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.cities.index', ['search' => 'La Libertad']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Trujillo']);
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
