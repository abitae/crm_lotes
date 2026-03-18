<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\Inmopro\Commission;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproLotsTest extends TestCase
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
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_guests_cannot_visit_lots_index(): void
    {
        $response = $this->get(route('inmopro.lots.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_lots_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.lots.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/inventory')->has('lots'));
    }

    public function test_authenticated_users_can_update_lot_status(): void
    {
        $user = User::factory()->create();
        $lot = Lot::first();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->first();
        $this->actingAs($user);

        $response = $this->patch(route('inmopro.lots.update', $lot), [
            'lot_status_id' => $statusReservado->id,
            'client_id' => $lot->client_id,
            'advisor_id' => $lot->advisor_id,
        ]);

        $response->assertRedirect();
        $lot->refresh();
        $this->assertSame((int) $statusReservado->id, (int) $lot->lot_status_id);
    }

    public function test_updating_lot_to_transferred_creates_commissions(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereNotNull('advisor_id')->where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->first()?->id)->first();
        $this->assertNotNull($lot, 'Need a non-transferred lot with advisor');
        $transferidoId = LotStatus::where('code', 'TRANSFERIDO')->first()->id;
        $this->actingAs($user);

        $countBefore = Commission::where('lot_id', $lot->id)->count();
        $response = $this->patch(route('inmopro.lots.update', $lot), [
            'lot_status_id' => $transferidoId,
            'client_id' => $lot->client_id,
            'advisor_id' => $lot->advisor_id,
        ]);

        $response->assertRedirect();
        $countAfter = Commission::where('lot_id', $lot->id)->count();
        $this->assertGreaterThan($countBefore, $countAfter, 'Commissions should be created when lot is marked as transferred');
    }

    public function test_updating_lot_with_new_client_name_only_creates_client(): void
    {
        $user = User::factory()->create();
        $lot = Lot::first();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->first();
        $this->actingAs($user);

        $response = $this->patch(route('inmopro.lots.update', $lot), [
            'lot_status_id' => $statusReservado->id,
            'client_id' => null,
            'advisor_id' => $lot->advisor_id,
            'client_name' => 'Cliente Solo Nombre',
            'client_dni' => null,
            'client_phone' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['name' => 'Cliente Solo Nombre']);
        $newClient = Client::where('name', 'Cliente Solo Nombre')->first();
        $this->assertNull($newClient->dni);
        $this->assertNull($newClient->phone);
        $lot->refresh();
        $this->assertSame($newClient->id, $lot->client_id);
    }
}
