<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
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
        $this->actingAs($user);

        $response = $this->post(route('inmopro.clients.store'), [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
            'phone' => '999888777',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('inmopro.clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
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
        $this->actingAs($user);

        Client::create(['name' => 'Juan Pérez', 'dni' => '11111111', 'phone' => '']);
        Client::create(['name' => 'María García', 'dni' => '22222222', 'phone' => '']);

        $response = $this->getJson(route('inmopro.clients.search', ['q' => 'Juan']));
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Juan Pérez']);

        $response2 = $this->getJson(route('inmopro.clients.search', ['q' => '2222']));
        $response2->assertOk();
        $response2->assertJsonFragment(['dni' => '22222222']);
    }
}
