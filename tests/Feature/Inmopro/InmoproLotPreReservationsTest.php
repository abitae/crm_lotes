<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\LotStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproLotPreReservationsTest extends TestCase
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
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_authenticated_users_can_visit_pre_reservations_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.lot-pre-reservations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/lot-pre-reservations/index')->has('preReservations'));
    }

    public function test_authenticated_users_can_approve_pre_reservation(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereHas('status', fn ($query) => $query->where('code', 'LIBRE'))->firstOrFail();
        $client = Client::firstOrFail();
        $advisor = Advisor::firstOrFail();
        $preReservationStatus = LotStatus::where('code', 'PRERESERVA')->firstOrFail();
        $reservedStatus = LotStatus::where('code', 'RESERVADO')->firstOrFail();

        $lot->update([
            'lot_status_id' => $preReservationStatus->id,
            'client_id' => $client->id,
            'advisor_id' => $advisor->id,
        ]);

        $preReservation = LotPreReservation::create([
            'lot_id' => $lot->id,
            'client_id' => $client->id,
            'advisor_id' => $advisor->id,
            'status' => 'PENDIENTE',
            'amount' => 1800,
            'voucher_path' => 'cazador/pre-reservations/test.png',
        ]);

        $this->actingAs($user)
            ->post(route('inmopro.lot-pre-reservations.approve', $preReservation))
            ->assertRedirect(route('inmopro.lot-pre-reservations.index'));

        $this->assertDatabaseHas('lot_pre_reservations', [
            'id' => $preReservation->id,
            'status' => 'APROBADA',
            'reviewed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $reservedStatus->id,
        ]);
    }
}
