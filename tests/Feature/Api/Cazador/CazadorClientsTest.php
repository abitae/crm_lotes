<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
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
