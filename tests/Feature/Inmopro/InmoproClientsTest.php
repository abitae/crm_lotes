<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
    }

    public function test_guests_cannot_visit_clients_index(): void
    {
        $response = $this->get(route('inmopro.clients.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_clients_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.clients.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/clients/index')->has('clients'));
    }

    public function test_authenticated_users_can_create_client(): void
    {
        $user = User::factory()->create();
        $type = ClientType::first();
        $advisor = Advisor::first();
        $city = City::first();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.clients.store'), [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
            'phone' => '999888777',
            'email' => 'test@example.com',
            'client_type_id' => $type->id,
            'advisor_id' => $advisor->id,
            'city_id' => $city?->id,
        ]);

        $response->assertRedirect(route('inmopro.clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
            'client_type_id' => $type->id,
            'advisor_id' => $advisor->id,
        ]);
    }

    public function test_authenticated_users_can_update_client(): void
    {
        $user = User::factory()->create();
        $client = Client::first();
        $this->actingAs($user);

        $response = $this->put(route('inmopro.clients.update', $client), [
            'name' => 'Cliente Actualizado',
            'dni' => $client->dni,
            'phone' => $client->phone,
            'email' => $client->email,
            'client_type_id' => $client->client_type_id,
            'advisor_id' => $client->advisor_id,
            'city_id' => $client->city_id,
        ]);

        $response->assertRedirect(route('inmopro.clients.index'));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Actualizado',
        ]);
    }

    public function test_clients_search_returns_json_with_like_match(): void
    {
        $user = User::factory()->create();
        $typeId = ClientType::first()->id;
        $advisorId = Advisor::first()->id;
        $this->actingAs($user);

        Client::create(['name' => 'Juan Perez', 'dni' => '11111111', 'phone' => '', 'client_type_id' => $typeId, 'advisor_id' => $advisorId]);
        Client::create(['name' => 'Maria Garcia', 'dni' => '22222222', 'phone' => '', 'client_type_id' => $typeId, 'advisor_id' => $advisorId]);

        $response = $this->getJson(route('inmopro.clients.search', ['q' => 'Juan']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Juan Perez']);

        $response2 = $this->getJson(route('inmopro.clients.search', ['q' => '2222']));
        $response2->assertOk();
        $response2->assertJsonFragment(['dni' => '22222222']);
    }
}
