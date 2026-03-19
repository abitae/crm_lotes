<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\LotStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->post(route('inmopro.lot-pre-reservations.approve', $preReservation), [
                'review_notes' => 'Voucher validado y monto conforme.',
            ])
            ->assertRedirect(route('inmopro.lot-pre-reservations.index'));

        $this->assertDatabaseHas('lot_pre_reservations', [
            'id' => $preReservation->id,
            'status' => 'APROBADA',
            'notes' => 'Voucher validado y monto conforme.',
            'reviewed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $reservedStatus->id,
        ]);
    }

    public function test_authenticated_users_can_register_pre_reservation(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $lot = Lot::whereHas('status', fn ($query) => $query->where('code', 'LIBRE'))->firstOrFail();
        $client = Client::query()->whereNotNull('advisor_id')->firstOrFail();
        $preReservationStatus = LotStatus::where('code', 'PRERESERVA')->firstOrFail();

        $this->actingAs($user)
            ->post(route('inmopro.lot-pre-reservations.store'), [
                'project_id' => $lot->project_id,
                'lot_id' => $lot->id,
                'advisor_id' => $client->advisor_id,
                'client_id' => $client->id,
                'amount' => 1500.50,
                'payment_reference' => 'OPER-2026-001',
                'notes' => 'Registro desde bandeja administrativa.',
                'voucher_image' => UploadedFile::fake()->image('voucher.png'),
            ])
            ->assertRedirect(route('inmopro.lot-pre-reservations.index'));

        $this->assertDatabaseHas('lot_pre_reservations', [
            'lot_id' => $lot->id,
            'client_id' => $client->id,
            'advisor_id' => $client->advisor_id,
            'status' => 'PENDIENTE',
            'amount' => 1500.50,
            'payment_reference' => 'OPER-2026-001',
            'notes' => 'Registro desde bandeja administrativa.',
        ]);

        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $preReservationStatus->id,
            'client_id' => $client->id,
            'advisor_id' => $client->advisor_id,
            'client_dni' => $client->dni,
        ]);

        $voucherPath = LotPreReservation::query()
            ->where('lot_id', $lot->id)
            ->value('voucher_path');

        $this->assertNotNull($voucherPath);
        Storage::disk('public')->assertExists($voucherPath);
    }
}
