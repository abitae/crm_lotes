<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\StorePreReservationRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotPreReservation;
use App\Services\Inmopro\LotStateTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PreReservationController extends Controller
{
    public function __construct(
        private LotStateTransitionService $lotStateTransitionService
    ) {}

    public function store(StorePreReservationRequest $request, Lot $lot): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $client = Client::query()
            ->whereKey($request->integer('client_id'))
            ->where('advisor_id', $advisor->id)
            ->whereHas('type', fn ($query) => $query->where('code', 'PROPIO'))
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'El cliente no pertenece al vendedor autenticado.',
            ], 422);
        }

        $lot->loadMissing(['status', 'project']);

        if ($request->integer('lot_id') !== $lot->id) {
            return response()->json([
                'message' => 'El lote enviado no coincide con la ruta.',
            ], 422);
        }

        if ((int) $lot->project_id !== $request->integer('project_id')) {
            return response()->json([
                'message' => 'El lote no pertenece al proyecto enviado.',
            ], 422);
        }

        if ($lot->status?->code !== 'LIBRE') {
            return response()->json([
                'message' => 'La unidad no está disponible para pre-reserva.',
            ], 422);
        }

        $hasActiveRequest = LotPreReservation::query()
            ->where('lot_id', $lot->id)
            ->whereIn('status', ['PENDIENTE', 'APROBADA'])
            ->exists();

        if ($hasActiveRequest) {
            return response()->json([
                'message' => 'La unidad ya tiene una pre-reserva activa.',
            ], 422);
        }

        $storedPath = $request->file('voucher_image')->store('cazador/pre-reservations', 'public');

        try {
            $preReservation = DB::transaction(function () use ($advisor, $client, $lot, $request, $storedPath) {
                $preReservation = LotPreReservation::create([
                    'lot_id' => $lot->id,
                    'client_id' => $client->id,
                    'advisor_id' => $advisor->id,
                    'status' => 'PENDIENTE',
                    'amount' => $request->input('amount'),
                    'voucher_path' => $storedPath,
                    'payment_reference' => $request->input('payment_reference'),
                    'notes' => $request->input('notes'),
                ]);

                $this->lotStateTransitionService->markAsPreReserved($lot, $client, $advisor);

                return $preReservation;
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Pre-reserva registrada y pendiente de aprobación.',
            'data' => [
                'id' => $preReservation->id,
                'status' => $preReservation->status,
                'amount' => $preReservation->amount,
                'project' => [
                    'id' => $lot->project?->id,
                    'name' => $lot->project?->name,
                ],
                'lot' => [
                    'id' => $lot->id,
                    'block' => $lot->block,
                    'number' => $lot->number,
                ],
                'voucher_url' => asset('storage/'.$preReservation->voucher_path),
            ],
        ], 201);
    }
}
