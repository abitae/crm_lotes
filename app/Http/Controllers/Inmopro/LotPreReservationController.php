<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ApproveLotPreReservationRequest;
use App\Http\Requests\Inmopro\RejectLotPreReservationRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\LotPreReservation;
use App\Models\Inmopro\Project;
use App\Services\Inmopro\LotStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LotPreReservationController extends Controller
{
    public function __construct(
        private LotStateTransitionService $lotStateTransitionService
    ) {}

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
        try {
            $this->lotStateTransitionService->approvePreReservation($lot_pre_reservation, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('inmopro.lot-pre-reservations.index');
    }

    public function reject(RejectLotPreReservationRequest $request, LotPreReservation $lot_pre_reservation): RedirectResponse
    {
        try {
            $this->lotStateTransitionService->rejectPreReservation(
                $lot_pre_reservation,
                $request->user(),
                (string) $request->input('rejection_reason')
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('inmopro.lot-pre-reservations.index');
    }
}
