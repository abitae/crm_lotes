<?php

namespace App\Services\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\LotTransferConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LotStateTransitionService
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function getStatusId(string $code): int
    {
        $statusId = LotStatus::query()->where('code', $code)->value('id');

        if (! $statusId) {
            throw new RuntimeException("No existe el estado {$code} configurado.");
        }

        return (int) $statusId;
    }

    public function canManuallySelectStatus(LotStatus $status): bool
    {
        return $status->code !== LotStatus::CODE_TRANSFERIDO;
    }

    public function assertManualStatusChangeAllowed(Lot $lot, int $targetStatusId): void
    {
        $targetStatus = LotStatus::query()->findOrFail($targetStatusId);
        $lot->loadMissing('status');

        if ($lot->status?->code === LotStatus::CODE_TRANSFERIDO && $lot->lot_status_id !== $targetStatus->id) {
            throw new RuntimeException('Los lotes transferidos no pueden cambiarse manualmente a otro estado.');
        }

        if (! $this->canManuallySelectStatus($targetStatus) && $lot->lot_status_id !== $targetStatus->id) {
            throw new RuntimeException('El estado TRANSFERIDO solo puede confirmarse desde el flujo especial de transferencia.');
        }
    }

    public function markAsPreReserved(Lot $lot, Client $client, Advisor $advisor): void
    {
        $lot->update([
            'lot_status_id' => $this->getStatusId(LotStatus::CODE_PRERESERVA),
            'client_id' => $client->id,
            'advisor_id' => $advisor->id,
            'client_name' => $client->name,
            'client_dni' => $client->dni,
        ]);
    }

    public function approvePreReservation(LotPreReservation $preReservation, ?User $reviewer): void
    {
        $preReservation->loadMissing(['lot.status', 'client']);

        if ($preReservation->status !== 'PENDIENTE') {
            throw new RuntimeException('Solo se pueden aprobar pre-reservas pendientes.');
        }

        if ($preReservation->lot?->status?->code !== LotStatus::CODE_PRERESERVA) {
            throw new RuntimeException('El lote ya no se encuentra en estado PRERESERVA.');
        }

        DB::transaction(function () use ($preReservation, $reviewer): void {
            $preReservation->update([
                'status' => 'APROBADA',
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $preReservation->lot()->update([
                'lot_status_id' => $this->getStatusId(LotStatus::CODE_RESERVADO),
                'client_id' => $preReservation->client_id,
                'advisor_id' => $preReservation->advisor_id,
                'client_name' => $preReservation->client?->name,
                'client_dni' => $preReservation->client?->dni,
            ]);
        });
    }

    public function rejectPreReservation(LotPreReservation $preReservation, ?User $reviewer, string $rejectionReason): void
    {
        $preReservation->loadMissing('lot.status');

        if ($preReservation->status !== 'PENDIENTE') {
            throw new RuntimeException('Solo se pueden rechazar pre-reservas pendientes.');
        }

        if ($preReservation->lot?->status?->code !== LotStatus::CODE_PRERESERVA) {
            throw new RuntimeException('El lote ya no se encuentra en estado PRERESERVA.');
        }

        DB::transaction(function () use ($preReservation, $reviewer, $rejectionReason): void {
            $preReservation->update([
                'status' => 'RECHAZADA',
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            $preReservation->lot()->update([
                'lot_status_id' => $this->getStatusId(LotStatus::CODE_LIBRE),
                'client_id' => null,
                'client_name' => null,
                'client_dni' => null,
                'advisor_id' => null,
            ]);
        });
    }

    public function confirmTransfer(Lot $lot, User $user, string $evidencePath, ?string $observations = null): LotTransferConfirmation
    {
        $lot->loadMissing(['status', 'transferConfirmations']);

        if ($lot->status?->code !== LotStatus::CODE_RESERVADO) {
            throw new RuntimeException('Solo se pueden confirmar transferencias de lotes en estado RESERVADO.');
        }

        if ($lot->transferConfirmations->isNotEmpty()) {
            throw new RuntimeException('El lote ya tiene una confirmación de transferencia registrada.');
        }

        return DB::transaction(function () use ($lot, $user, $evidencePath, $observations): LotTransferConfirmation {
            $confirmation = LotTransferConfirmation::create([
                'lot_id' => $lot->id,
                'confirmed_by' => $user->id,
                'evidence_path' => $evidencePath,
                'observations' => $observations,
                'confirmed_at' => now(),
            ]);

            $lot->update([
                'lot_status_id' => $this->getStatusId(LotStatus::CODE_TRANSFERIDO),
                'notarial_transfer_date' => $lot->notarial_transfer_date ?? now()->toDateString(),
            ]);

            $this->commissionService->createCommissionsForTransferredLot($lot->fresh());

            return $confirmation;
        });
    }
}
