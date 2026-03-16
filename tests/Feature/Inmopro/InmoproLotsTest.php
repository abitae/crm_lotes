<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\Inmopro\Commission;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InmoproLotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AuthorizationSeeder::class);
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

    public function test_confirming_lot_transfer_creates_commissions(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([Role::where('code', 'ADMIN')->firstOrFail()->id]);
        $lot = Lot::whereNotNull('advisor_id')->whereHas('status', fn ($query) => $query->where('code', 'RESERVADO'))->first();
        $this->assertNotNull($lot, 'Need a reserved lot with advisor');
        $this->actingAs($user);

        $countBefore = Commission::where('lot_id', $lot->id)->count();
        $response = $this->post(route('inmopro.lots.transfer-confirmation.store', $lot), [
            'evidence_image' => UploadedFile::fake()->image('transfer.png'),
        ]);

        $response->assertRedirect(route('inmopro.lots.show', $lot));
        $countAfter = Commission::where('lot_id', $lot->id)->count();
        $this->assertGreaterThan($countBefore, $countAfter, 'Commissions should be created when lot is marked as transferred');
    }

    public function test_manual_update_cannot_move_lot_to_transferred(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereNotNull('advisor_id')->where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->first()?->id)->firstOrFail();
        $transferidoId = LotStatus::where('code', 'TRANSFERIDO')->firstOrFail()->id;

        $this->actingAs($user)
            ->patch(route('inmopro.lots.update', $lot), [
                'lot_status_id' => $transferidoId,
                'client_id' => $lot->client_id,
                'advisor_id' => $lot->advisor_id,
            ])
            ->assertSessionHasErrors('lot_status_id');

        $lot->refresh();
        $this->assertNotSame($transferidoId, (int) $lot->lot_status_id);
    }

    public function test_updating_lot_with_new_client_name_only_creates_client(): void
    {
        $user = User::factory()->create();
        $lot = Lot::where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->firstOrFail()->id)->firstOrFail();
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

    public function test_updating_lot_calculates_remaining_balance_and_normalizes_dates(): void
    {
        $user = User::factory()->create();
        $lot = Lot::where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->firstOrFail()->id)->firstOrFail();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('inmopro.lots.update', $lot), [
                'lot_status_id' => $statusReservado->id,
                'client_id' => $lot->client_id,
                'advisor_id' => $lot->advisor_id,
                'price' => 50000,
                'advance' => 12500,
                'remaining_balance' => 1,
                'payment_limit_date' => '15/03/2026',
                'contract_date' => '16/03/2026',
                'notarial_transfer_date' => '2026-03-17T14:30:00',
            ])
            ->assertRedirect();

        $lot->refresh();

        $this->assertSame('37500.00', $lot->remaining_balance);
        $this->assertSame('2026-03-15', $lot->payment_limit_date?->toDateString());
        $this->assertSame('2026-03-16', $lot->contract_date?->toDateString());
        $this->assertSame('2026-03-17', $lot->notarial_transfer_date?->toDateString());
    }

    public function test_updating_lot_rejects_advance_greater_than_price(): void
    {
        $user = User::factory()->create();
        $lot = Lot::where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->firstOrFail()->id)->firstOrFail();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('inmopro.lots.update', $lot), [
                'lot_status_id' => $statusReservado->id,
                'client_id' => $lot->client_id,
                'advisor_id' => $lot->advisor_id,
                'price' => 10000,
                'advance' => 12000,
            ])
            ->assertSessionHasErrors('advance');
    }
}
