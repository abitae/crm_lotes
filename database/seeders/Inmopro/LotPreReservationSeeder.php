<?php

namespace Database\Seeders\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\LotStatus;
use Illuminate\Database\Seeder;

class LotPreReservationSeeder extends Seeder
{
    public function run(): void
    {
        $ownTypeId = ClientType::query()->where('code', 'PROPIO')->value('id');
        $freeStatusId = LotStatus::query()->where('code', 'LIBRE')->value('id');
        $preReservationStatusId = LotStatus::query()->where('code', 'PRERESERVA')->value('id');
        $reservedStatusId = LotStatus::query()->where('code', 'RESERVADO')->value('id');

        if (! $ownTypeId || ! $freeStatusId || ! $preReservationStatusId || ! $reservedStatusId) {
            return;
        }

        $clients = Client::query()
            ->where('client_type_id', $ownTypeId)
            ->with('advisor')
            ->limit(3)
            ->get()
            ->values();

        $lots = Lot::query()
            ->where('lot_status_id', $freeStatusId)
            ->orderBy('project_id')
            ->orderBy('block')
            ->orderBy('number')
            ->limit(3)
            ->get()
            ->values();

        if ($clients->count() < 3 || $lots->count() < 3) {
            return;
        }

        $seedRows = [
            ['status' => 'PENDIENTE', 'lot_status_id' => $preReservationStatusId, 'amount' => 1200, 'reviewed_at' => null, 'rejection_reason' => null],
            ['status' => 'APROBADA', 'lot_status_id' => $reservedStatusId, 'amount' => 2500, 'reviewed_at' => now()->subDay(), 'rejection_reason' => null],
            ['status' => 'RECHAZADA', 'lot_status_id' => $freeStatusId, 'amount' => 900, 'reviewed_at' => now()->subHours(12), 'rejection_reason' => 'Voucher ilegible o no corresponde al monto informado.'],
        ];

        foreach ($seedRows as $index => $seedRow) {
            $client = $clients[$index];
            $lot = $lots[$index];

            LotPreReservation::updateOrCreate(
                ['lot_id' => $lot->id],
                [
                    'client_id' => $client->id,
                    'advisor_id' => $client->advisor_id,
                    'status' => $seedRow['status'],
                    'amount' => $seedRow['amount'],
                    'voucher_path' => 'seeders/cazador-voucher-'.($index + 1).'.png',
                    'payment_reference' => 'SEED-PR-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'notes' => 'Pre-reserva generada por seeder para pruebas y demostración.',
                    'reviewed_at' => $seedRow['reviewed_at'],
                    'rejection_reason' => $seedRow['rejection_reason'],
                ]
            );

            $lot->update([
                'lot_status_id' => $seedRow['lot_status_id'],
                'client_id' => $seedRow['status'] === 'RECHAZADA' ? null : $client->id,
                'advisor_id' => $seedRow['status'] === 'RECHAZADA' ? null : $client->advisor_id,
                'client_name' => $seedRow['status'] === 'RECHAZADA' ? null : $client->name,
                'client_dni' => $seedRow['status'] === 'RECHAZADA' ? null : $client->dni,
            ]);
        }
    }
}
