<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorClientsTest extends TestCase
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

    public function test_advisor_can_create_and_list_own_clients(): void
    {
        $advisor = Advisor::firstOrFail();
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $city = City::firstOrFail();
        $token = $this->loginToken($advisor);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.cazador.clients.store'), [
                'name' => 'Cliente Cazador',
                'dni' => '76543210',
                'phone' => '987654321',
                'email' => 'cliente@cazador.test',
                'city_id' => $city->id,
            ])->assertCreated();

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Cazador',
            'advisor_id' => $advisor->id,
            'client_type_id' => $ownType->id,
            'city_id' => $city->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.clients.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cliente Cazador']);
    }

    public function test_advisor_cannot_register_client_with_duplicate_phone(): void
    {
        $ownerAdvisor = Advisor::firstOrFail();
        $advisor = Advisor::query()->whereKeyNot($ownerAdvisor->id)->firstOrFail();
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $city = City::firstOrFail();

        $existing = Client::create([
            'name' => 'Cliente existente',
            'dni' => '11110001',
            'phone' => '980000099',
            'client_type_id' => $ownType->id,
            'advisor_id' => $ownerAdvisor->id,
            'city_id' => $city->id,
        ]);
        $existing->load('advisor');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.clients.store'), [
                'name' => 'Intento duplicado',
                'dni' => '87654321',
                'phone' => $existing->phone,
                'email' => 'dup@test.com',
                'city_id' => $city->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.duplicate_registration.0', 'Cliente ya registrado por '.$existing->advisor->name);

        $this->assertDatabaseMissing('clients', [
            'name' => 'Intento duplicado',
            'advisor_id' => $advisor->id,
            'client_type_id' => $ownType->id,
        ]);
    }

    public function test_advisor_cannot_register_client_with_duplicate_dni(): void
    {
        $ownerAdvisor = Advisor::firstOrFail();
        $advisor = Advisor::query()->whereKeyNot($ownerAdvisor->id)->firstOrFail();
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $city = City::firstOrFail();

        $existing = Client::create([
            'name' => 'Cliente con DNI',
            'dni' => '11110002',
            'phone' => '980000088',
            'client_type_id' => $ownType->id,
            'advisor_id' => $ownerAdvisor->id,
            'city_id' => $city->id,
        ]);
        $existing->load('advisor');

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.clients.store'), [
                'name' => 'Otro nombre',
                'dni' => $existing->dni,
                'phone' => '911000999',
                'city_id' => $city->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.duplicate_registration.0', 'Cliente ya registrado por '.$existing->advisor->name);
    }

    public function test_advisor_cannot_access_clients_from_another_advisor(): void
    {
        $advisor = Advisor::firstOrFail();
        $otherAdvisor = Advisor::query()->whereKeyNot($advisor->id)->firstOrFail();
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $client = Client::create([
            'name' => 'Cliente Ajeno',
            'dni' => '10000001',
            'phone' => '900000001',
            'client_type_id' => $ownType->id,
            'advisor_id' => $otherAdvisor->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.clients.show', $client))
            ->assertNotFound();
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
