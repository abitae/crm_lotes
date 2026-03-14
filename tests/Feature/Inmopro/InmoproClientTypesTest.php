<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\ClientType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproClientTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
    }

    public function test_authenticated_users_can_visit_client_types_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.client-types.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/client-types/index')->has('clientTypes'));
    }

    public function test_authenticated_users_can_create_client_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('inmopro.client-types.store'), [
                'name' => 'Broker',
                'code' => 'BROKER',
                'description' => 'Intermediario',
                'color' => '#111111',
                'sort_order' => 8,
                'is_active' => true,
            ])
            ->assertRedirect(route('inmopro.client-types.index'));

        $this->assertDatabaseHas('client_types', [
            'name' => 'Broker',
            'code' => 'BROKER',
        ]);
    }

    public function test_authenticated_users_can_update_client_type(): void
    {
        $user = User::factory()->create();
        $clientType = ClientType::first();

        $this->actingAs($user)
            ->put(route('inmopro.client-types.update', $clientType), [
                'name' => 'Tipo Actualizado',
                'code' => $clientType->code,
                'description' => 'Actualizado',
                'color' => '#222222',
                'sort_order' => 2,
                'is_active' => true,
            ])
            ->assertRedirect(route('inmopro.client-types.index'));

        $this->assertDatabaseHas('client_types', [
            'id' => $clientType->id,
            'name' => 'Tipo Actualizado',
        ]);
    }
}
