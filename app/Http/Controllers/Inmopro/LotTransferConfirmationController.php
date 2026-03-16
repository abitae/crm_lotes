<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\ConfirmLotTransferRequest;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Services\Inmopro\LotStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LotTransferConfirmationController extends Controller
{
    public function __construct(
        private LotStateTransitionService $lotStateTransitionService
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('confirm-lot-transfer'), 403);

        $reservedStatusId = $this->lotStateTransitionService->getStatusId(LotStatus::CODE_RESERVADO);
        $search = trim((string) $request->input('search', ''));

        $lots = Lot::query()
            ->with(['project:id,name', 'client:id,name,dni,phone', 'advisor:id,name'])
            ->where('lot_status_id', $reservedStatusId)
            ->whereDoesntHave('transferConfirmations')
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('block', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('client_dni', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('dni', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('project_id')
            ->orderBy('block')
            ->orderBy('number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('inmopro/lot-transfer-confirmations/index', [
            'lots' => $lots,
            'filters' => [
                'search' => $search,
                'project_id' => $request->filled('project_id') ? (string) $request->integer('project_id') : '',
            ],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Lot $lot): Response
    {
        $lot->load([
            'project',
            'status',
            'client',
            'advisor',
            'transferConfirmations.confirmer',
        ]);

        abort_unless(request()->user()?->can('confirm-lot-transfer'), 403);
        abort_unless($lot->status?->code === LotStatus::CODE_RESERVADO, 422, 'Solo se pueden confirmar transferencias de lotes en estado RESERVADO.');

        return Inertia::render('inmopro/lots/transfer-confirmation', [
            'lot' => $lot,
        ]);
    }

    public function store(ConfirmLotTransferRequest $request, Lot $lot): RedirectResponse
    {
        $lot->loadMissing(['status', 'transferConfirmations']);

        if ($lot->status?->code !== LotStatus::CODE_RESERVADO) {
            return back()->with('error', 'Solo se pueden confirmar transferencias de lotes en estado RESERVADO.');
        }

        if ($lot->transferConfirmations->isNotEmpty()) {
            return back()->with('error', 'El lote ya tiene una confirmación de transferencia registrada.');
        }

        $path = $request->file('evidence_image')->store('inmopro/lot-transfer-confirmations', 'public');

        try {
            $this->lotStateTransitionService->confirmTransfer(
                $lot,
                $request->user(),
                $path,
                $request->input('observations')
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inmopro.lots.show', $lot)
            ->with('success', 'Transferencia confirmada correctamente.');
    }
}
