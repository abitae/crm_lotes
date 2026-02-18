<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreLotRequest;
use App\Http\Requests\Inmopro\UpdateLotRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use App\Services\Inmopro\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LotController extends Controller
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function index(Request $request): Response
    {
        $projectId = $request->query('project_id');
        $project = $projectId ? Project::find($projectId) : Project::orderBy('name')->first();

        if (! $project) {
            return Inertia::render('inmopro/inventory', [
                'projects' => Project::orderBy('name')->get(),
                'project' => null,
                'lots' => [],
                'lotStatuses' => LotStatus::orderBy('sort_order')->get(),
                'clients' => [],
                'advisors' => [],
            ]);
        }

        $lots = Lot::with(['status', 'client', 'advisor'])
            ->where('project_id', $project->id)
            ->orderBy('block')
            ->orderBy('number')
            ->get();

        $projects = Project::orderBy('name')->get();
        $lotStatuses = LotStatus::orderBy('sort_order')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'dni', 'phone', 'email']);
        $advisors = Advisor::with('level')->orderBy('name')->get();

        return Inertia::render('inmopro/inventory', [
            'projects' => $projects,
            'project' => $project,
            'lots' => $lots,
            'lotStatuses' => $lotStatuses,
            'clients' => $clients,
            'advisors' => $advisors,
        ]);
    }

    public function update(UpdateLotRequest $request, Lot $lot): RedirectResponse
    {
        $transferidoCode = LotStatus::where('code', 'TRANSFERIDO')->first()?->id;
        $previousStatusId = $lot->lot_status_id;

        $validated = $request->validated();
        $clientName = isset($validated['client_name']) ? trim((string) $validated['client_name']) : null;
        $clientDni = isset($validated['client_dni']) ? trim((string) $validated['client_dni']) : null;
        $clientPhone = isset($validated['client_phone']) ? trim((string) $validated['client_phone']) : null;

        if ($clientName === '' || $clientName === null) {
            $validated['client_id'] = null;
            $validated['client_name'] = null;
            $validated['client_dni'] = null;
            $validated['client_phone'] = null;
        } else {
            $clientId = isset($validated['client_id']) ? (int) $validated['client_id'] : null;
            $client = $clientId > 0 ? Client::find($clientId) : null;

            if ($client === null && $clientDni !== '' && $clientDni !== null) {
                $client = Client::where('dni', $clientDni)->first();
            }
            if ($client === null && $clientName !== '') {
                $client = Client::where('name', $clientName)->first();
            }

            if ($client) {
                $client->update([
                    'name' => $clientName,
                    'dni' => $clientDni ?? $client->dni,
                    'phone' => $clientPhone ?? $client->phone,
                ]);
                $validated['client_id'] = $client->id;
            } else {
                $client = Client::create([
                    'name' => $clientName,
                    'dni' => $clientDni ?: null,
                    'phone' => $clientPhone ?: null,
                ]);
                $validated['client_id'] = $client->id;
            }
            $validated['client_name'] = $clientName;
            $validated['client_dni'] = $clientDni;
            $validated['client_phone'] = $clientPhone;
        }

        $lot->fill($validated);
        $lot->save();

        if ($transferidoCode && (int) $request->input('lot_status_id') === (int) $transferidoCode && (int) $previousStatusId !== (int) $transferidoCode) {
            $this->commissionService->createCommissionsForTransferredLot($lot->fresh());
        }

        return back();
    }

    public function create(Request $request): Response
    {
        $projectId = $request->query('project_id');
        $project = $projectId ? Project::find($projectId) : null;
        $lotStatuses = LotStatus::orderBy('sort_order')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'dni', 'phone', 'email']);
        $advisors = Advisor::with('level')->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return Inertia::render('inmopro/lots/create', [
            'projects' => $projects,
            'project' => $project,
            'lotStatuses' => $lotStatuses,
            'clients' => $clients,
            'advisors' => $advisors,
        ]);
    }

    public function store(StoreLotRequest $request): RedirectResponse
    {
        $lot = Lot::create($request->validated());

        return redirect()->route('inmopro.lots.index', ['project_id' => $lot->project_id]);
    }

    public function show(Lot $lot): Response
    {
        $lot->load(['project', 'status', 'client', 'advisor', 'commissions']);

        return Inertia::render('inmopro/lots/show', [
            'lot' => $lot,
        ]);
    }

    public function edit(Lot $lot): Response
    {
        $lot->load(['project', 'status', 'client', 'advisor']);
        $lotStatuses = LotStatus::orderBy('sort_order')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'dni', 'phone', 'email']);
        $advisors = Advisor::with('level')->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return Inertia::render('inmopro/lots/edit', [
            'lot' => $lot,
            'lotStatuses' => $lotStatuses,
            'clients' => $clients,
            'advisors' => $advisors,
            'projects' => $projects,
        ]);
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $projectId = $lot->project_id;
        $lot->delete();

        return redirect()->route('inmopro.lots.index', ['project_id' => $projectId]);
    }
}
