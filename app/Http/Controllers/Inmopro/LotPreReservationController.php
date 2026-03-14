<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ApproveLotPreReservationRequest;
use App\Http\Requests\Inmopro\RejectLotPreReservationRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LotPreReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $preReservations = LotPreReservation::query()
            ->with(['lot.project', 'lot.status', 'client.city', 'advisor.team', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('project_id'), function ($query) use ($request) {
                $query->whereHas('lot', fn ($lotQuery) => $lotQuery->where('project_id', $request->integer('project_id')));
            })
            ->when($request->filled('advisor_id'), fn ($query) => $query->where('advisor_id', $request->integer('advisor_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('inmopro/lot-pre-reservations/index', [
            'preReservations' => $preReservations,
            'filters' => $request->only('status', 'project_id', 'advisor_id'),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'advisors' => Advisor::query()->with('team')->orderBy('name')->get(['id', 'name', 'team_id']),
        ]);
    }

    public function approve(ApproveLotPreReservationRequest $request, LotPreReservation $lot_pre_reservation): RedirectResponse
    {
        $reservedStatusId = LotStatus::query()->where('code', 'RESERVADO')->value('id');

        DB::transaction(function () use ($lot_pre_reservation, $request, $reservedStatusId) {
            $lot_pre_reservation->update([
                'status' => 'APROBADA',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $lot_pre_reservation->lot()->update([
                'lot_status_id' => $reservedStatusId,
                'client_id' => $lot_pre_reservation->client_id,
                'advisor_id' => $lot_pre_reservation->advisor_id,
                'client_name' => $lot_pre_reservation->client?->name,
                'client_dni' => $lot_pre_reservation->client?->dni,
            ]);
        });

        return redirect()->route('inmopro.lot-pre-reservations.index');
    }

    public function reject(RejectLotPreReservationRequest $request, LotPreReservation $lot_pre_reservation): RedirectResponse
    {
        $freeStatusId = LotStatus::query()->where('code', 'LIBRE')->value('id');

        DB::transaction(function () use ($lot_pre_reservation, $request, $freeStatusId) {
            $lot_pre_reservation->update([
                'status' => 'RECHAZADA',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'rejection_reason' => $request->input('rejection_reason'),
            ]);

            $lot_pre_reservation->lot()->update([
                'lot_status_id' => $freeStatusId,
                'client_id' => null,
                'client_name' => null,
                'client_dni' => null,
                'advisor_id' => null,
            ]);
        });

        return redirect()->route('inmopro.lot-pre-reservations.index');
    }
}
