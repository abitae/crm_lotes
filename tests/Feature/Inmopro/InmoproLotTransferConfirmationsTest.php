<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Commission;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\LotTransferConfirmation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InmoproLotTransferConfirmationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, int>
     */
    private array $statusIds;

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

        $this->statusIds = [
            'RESERVADO' => (int) LotStatus::query()->where('code', 'RESERVADO')->value('id'),
            'TRANSFERIDO' => (int) LotStatus::query()->where('code', 'TRANSFERIDO')->value('id'),
        ];
    }

    public function test_authorized_user_can_visit_transfer_confirmations_index(): void
    {
        $user = $this->createTransferManager();

        $this->actingAs($user)
            ->get(route('inmopro.lot-transfer-confirmations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/lot-transfer-confirmations/index')->has('lots'));
    }

    public function test_authorized_user_can_register_transfer_for_reserved_lot(): void
    {
        Storage::fake('public');

        $user = $this->createTransferManager();
        $lot = $this->makeReservedLot();

        $this->actingAs($user)
            ->post(route('inmopro.lots.transfer-confirmation.store', $lot), [
                'evidence_image' => UploadedFile::fake()->image('voucher.png'),
            ])
            ->assertRedirect(route('inmopro.lot-transfer-confirmations.index'));

        $this->assertDatabaseHas('lot_transfer_confirmations', [
            'lot_id' => $lot->id,
            'status' => LotTransferConfirmation::STATUS_PENDING,
            'requested_by' => $user->id,
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $this->statusIds['TRANSFERIDO'],
        ]);
    }

    public function test_approving_pending_transfer_marks_review_and_creates_commissions(): void
    {
        $user = $this->createTransferManager();
        $lot = $this->makeReservedLot();

        $lot->update([
            'lot_status_id' => $this->statusIds['TRANSFERIDO'],
        ]);

        $transfer = LotTransferConfirmation::create([
            'lot_id' => $lot->id,
            'status' => LotTransferConfirmation::STATUS_PENDING,
            'evidence_path' => 'inmopro/lot-transfer-confirmations/test.png',
            'requested_by' => $user->id,
        ]);

        $countBefore = Commission::query()->where('lot_id', $lot->id)->count();

        $this->actingAs($user)
            ->post(route('inmopro.lot-transfer-confirmations.approve', $transfer), [
                'review_notes' => 'Se valida la evidencia y coincide con la operación.',
            ])
            ->assertRedirect(route('inmopro.lot-transfer-confirmations.index'));

        $this->assertDatabaseHas('lot_transfer_confirmations', [
            'id' => $transfer->id,
            'status' => LotTransferConfirmation::STATUS_APPROVED,
            'review_notes' => 'Se valida la evidencia y coincide con la operación.',
            'reviewed_by' => $user->id,
        ]);
        $this->assertGreaterThan(
            $countBefore,
            Commission::query()->where('lot_id', $lot->id)->count(),
            'Se deben crear comisiones al aprobar la transferencia.'
        );
    }

    public function test_rejecting_pending_transfer_returns_lot_to_reserved(): void
    {
        $user = $this->createTransferManager();
        $lot = $this->makeReservedLot();

        $lot->update([
            'lot_status_id' => $this->statusIds['TRANSFERIDO'],
        ]);

        $transfer = LotTransferConfirmation::create([
            'lot_id' => $lot->id,
            'status' => LotTransferConfirmation::STATUS_PENDING,
            'evidence_path' => 'inmopro/lot-transfer-confirmations/test.png',
            'requested_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('inmopro.lot-transfer-confirmations.reject', $transfer), [
                'rejection_reason' => 'Voucher ilegible',
            ])
            ->assertRedirect(route('inmopro.lot-transfer-confirmations.index'));

        $this->assertDatabaseHas('lot_transfer_confirmations', [
            'id' => $transfer->id,
            'status' => LotTransferConfirmation::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'rejection_reason' => 'Voucher ilegible',
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $this->statusIds['RESERVADO'],
        ]);
    }

    private function createTransferManager(): User
    {
        $user = User::factory()->create();
        $permission = Permission::query()->create([
            'name' => 'Confirmar transferencia de lotes',
            'code' => 'confirm-lot-transfer',
            'is_system' => true,
        ]);
        $role = Role::query()->create([
            'name' => 'Administrador transferencias',
            'code' => 'TRANSFER_ADMIN',
            'is_system' => true,
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function makeReservedLot(): Lot
    {
        $lot = Lot::query()->firstOrFail();
        $client = Client::query()->firstOrFail();
        $advisor = Advisor::query()->firstOrFail();

        $lot->update([
            'lot_status_id' => $this->statusIds['RESERVADO'],
            'client_id' => $client->id,
            'advisor_id' => $advisor->id,
            'client_name' => $client->name,
            'client_dni' => $client->dni,
            'price' => 10000,
            'contract_date' => now()->toDateString(),
        ]);

        return $lot->fresh();
    }
}
