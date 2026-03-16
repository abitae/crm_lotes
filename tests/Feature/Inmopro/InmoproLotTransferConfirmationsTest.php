<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\LotTransferConfirmation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InmoproLotTransferConfirmationsTest extends TestCase
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
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_user_with_permission_can_confirm_lot_transfer(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantTransferPermission($user);

        $lot = Lot::query()
            ->whereHas('status', fn ($query) => $query->where('code', 'RESERVADO'))
            ->whereNotNull('advisor_id')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('inmopro.lots.transfer-confirmation.store', $lot), [
                'evidence_image' => UploadedFile::fake()->image('transferencia.png'),
                'observations' => 'Escritura validada.',
            ])
            ->assertRedirect(route('inmopro.lots.show', $lot));

        $transferStatus = LotStatus::query()->where('code', 'TRANSFERIDO')->firstOrFail();

        $this->assertDatabaseHas('lot_transfer_confirmations', [
            'lot_id' => $lot->id,
            'confirmed_by' => $user->id,
            'observations' => 'Escritura validada.',
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $transferStatus->id,
        ]);

        $confirmation = LotTransferConfirmation::query()->where('lot_id', $lot->id)->firstOrFail();
        Storage::disk('public')->assertExists($confirmation->evidence_path);
    }

    public function test_user_with_permission_can_visit_transfer_confirmation_index(): void
    {
        $user = User::factory()->create();
        $this->grantTransferPermission($user);

        $this->actingAs($user)
            ->get(route('inmopro.lot-transfer-confirmations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/lot-transfer-confirmations/index')
                ->has('lots.data'));
    }

    public function test_transfer_confirmation_index_filters_reserved_lots(): void
    {
        $user = User::factory()->create();
        $this->grantTransferPermission($user);

        $reservedStatus = LotStatus::query()->where('code', 'RESERVADO')->firstOrFail();
        $freeStatus = LotStatus::query()->where('code', 'LIBRE')->firstOrFail();
        $client = Client::query()->firstOrFail();

        $lot = Lot::query()->firstOrFail();
        $otherLot = Lot::query()->skip(1)->firstOrFail();

        $lot->update([
            'lot_status_id' => $reservedStatus->id,
            'client_id' => $client->id,
            'client_name' => 'Cliente Transferencia',
            'client_dni' => '70001122',
            'block' => 'ZZTRUNICO',
            'number' => 501,
        ]);
        $client->update([
            'name' => 'Cliente Transferencia',
            'dni' => '70001122',
            'phone' => '999888777',
        ]);

        $otherLot->update([
            'lot_status_id' => $freeStatus->id,
            'block' => 'XX',
            'number' => 999,
        ]);

        $this->actingAs($user)
            ->get(route('inmopro.lot-transfer-confirmations.index', [
                'search' => 'ZZTRUNICO',
                'project_id' => $lot->project_id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/lot-transfer-confirmations/index')
                ->has('lots.data', 1)
                ->where('lots.data.0.id', $lot->id));
    }

    public function test_user_without_permission_cannot_access_transfer_confirmation_view(): void
    {
        $user = User::factory()->create();
        $lot = Lot::query()->whereHas('status', fn ($query) => $query->where('code', 'RESERVADO'))->firstOrFail();

        $this->actingAs($user)
            ->get(route('inmopro.lots.transfer-confirmation.create', $lot))
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_transfer_confirmation_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.lot-transfer-confirmations.index'))
            ->assertForbidden();
    }

    public function test_transfer_confirmation_requires_reserved_lot(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantTransferPermission($user);
        $lot = Lot::query()->whereHas('status', fn ($query) => $query->where('code', 'LIBRE'))->firstOrFail();

        $this->actingAs($user)
            ->post(route('inmopro.lots.transfer-confirmation.store', $lot), [
                'evidence_image' => UploadedFile::fake()->image('transferencia.png'),
            ])
            ->assertSessionHas('error', 'Solo se pueden confirmar transferencias de lotes en estado RESERVADO.');

        $this->assertDatabaseMissing('lot_transfer_confirmations', [
            'lot_id' => $lot->id,
        ]);
    }

    private function grantTransferPermission(User $user): void
    {
        $role = Role::query()->where('code', 'ADMIN')->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
