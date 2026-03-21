<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Datero;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorDaterosTest extends TestCase
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

    public function test_dateros_routes_require_authentication(): void
    {
        $this->getJson(route('api.v1.cazador.dateros.index'))
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'No autenticado.']);
    }

    public function test_advisor_can_list_create_and_update_own_dateros(): void
    {
        $advisor = Advisor::firstOrFail();
        $city = City::firstOrFail();
        $token = $this->loginToken($advisor);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dateros.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.cazador.dateros.store'), [
                'name' => 'Datero API',
                'phone' => '987111222',
                'email' => 'datero.api@test.com',
                'city_id' => $city->id,
                'dni' => '44112233',
                'username' => 'datero_api_cazador',
                'pin' => '654321',
                'is_active' => true,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Datero API')
            ->assertJsonPath('data.username', 'datero_api_cazador');

        $dateroId = (int) $create->json('data.id');

        $this->assertDatabaseHas('dateros', [
            'id' => $dateroId,
            'advisor_id' => $advisor->id,
            'dni' => '44112233',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dateros.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['username' => 'datero_api_cazador']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dateros.index', ['search' => 'API']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $update = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.v1.cazador.dateros.update', $dateroId), [
                'name' => 'Datero API Actualizado',
                'phone' => '987111223',
                'email' => 'datero.api2@test.com',
                'city_id' => $city->id,
                'dni' => '44112233',
                'username' => 'datero_api_cazador',
                'is_active' => false,
            ]);

        $update->assertOk()
            ->assertJsonPath('data.name', 'Datero API Actualizado')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('dateros', [
            'id' => $dateroId,
            'name' => 'Datero API Actualizado',
            'is_active' => false,
        ]);
    }

    public function test_advisor_cannot_update_another_advisors_datero(): void
    {
        $owner = Advisor::firstOrFail();
        $other = Advisor::query()->whereKeyNot($owner->id)->firstOrFail();
        $city = City::firstOrFail();

        $datero = Datero::create([
            'advisor_id' => $other->id,
            'name' => 'Datero ajeno',
            'phone' => '900000011',
            'email' => 'ajeno@test.com',
            'city_id' => $city->id,
            'dni' => '99887766',
            'username' => 'datero_ajeno_cazador',
            'pin' => '111111',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($owner))
            ->putJson(route('api.v1.cazador.dateros.update', $datero), [
                'name' => 'Intento',
                'phone' => '900000012',
                'email' => 'intento@test.com',
                'city_id' => $city->id,
                'dni' => '99887766',
                'username' => 'datero_ajeno_cazador',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('dateros', [
            'id' => $datero->id,
            'name' => 'Datero ajeno',
        ]);
    }

    public function test_store_rejects_username_equal_to_advisor_username(): void
    {
        $advisor = Advisor::firstOrFail();
        $city = City::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.dateros.store'), [
                'name' => 'X',
                'phone' => '900000013',
                'email' => 'x@test.com',
                'city_id' => $city->id,
                'dni' => '11223344',
                'username' => $advisor->username,
                'pin' => '123456',
            ])
            ->assertUnprocessable();
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
