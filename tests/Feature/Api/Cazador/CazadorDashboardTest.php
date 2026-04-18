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

class CazadorDashboardTest extends TestCase
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

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson(route('api.v1.cazador.dashboard.show'))
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'No autenticado.']);
    }

    public function test_advisor_receives_dashboard_statistics_payload(): void
    {
        $advisor = Advisor::firstOrFail();
        $token = $this->loginToken($advisor);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dashboard.show'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'clients_count',
                    'clients' => [
                        'total',
                        'propio',
                        'datero',
                    ],
                    'pre_reservations' => [
                        'active',
                        'pending',
                        'approved',
                        'rejected',
                    ],
                    'lots' => [
                        'pre_reservation',
                        'reserved',
                        'transferred',
                        'installments',
                    ],
                    'attention_tickets_pending',
                    'reminders_pending',
                ],
            ]);
    }

    public function test_clients_count_reflects_new_propio_client(): void
    {
        $advisor = Advisor::firstOrFail();
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $city = City::firstOrFail();
        $token = $this->loginToken($advisor);

        $before = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dashboard.show'))
            ->json('data.clients_count');

        Client::create([
            'name' => 'Cliente Dashboard',
            'dni' => '87654321',
            'phone' => '987000111',
            'email' => 'dash-client@test.local',
            'client_type_id' => $ownType->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);

        $after = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dashboard.show'))
            ->json('data.clients_count');

        $this->assertSame($before + 1, $after);
    }

    public function test_dashboard_breaks_down_clients_by_type(): void
    {
        $advisor = Advisor::firstOrFail();
        $city = City::firstOrFail();
        $propioType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $dateroType = ClientType::where('code', 'DATERO')->firstOrFail();
        $token = $this->loginToken($advisor);

        Client::create([
            'name' => 'Cliente propio dashboard',
            'dni' => '87654322',
            'phone' => '987000112',
            'email' => 'dash-propio@test.local',
            'client_type_id' => $propioType->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);

        Client::create([
            'name' => 'Cliente datero dashboard',
            'dni' => '87654323',
            'phone' => '987000113',
            'email' => 'dash-datero@test.local',
            'client_type_id' => $dateroType->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);

        $clients = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.cazador.dashboard.show'))
            ->assertOk()
            ->json('data.clients');

        $this->assertNotNull($clients);
        $this->assertSame($clients['propio'] + $clients['datero'], $clients['total']);
        $this->assertGreaterThanOrEqual(1, $clients['propio']);
        $this->assertGreaterThanOrEqual(1, $clients['datero']);
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
