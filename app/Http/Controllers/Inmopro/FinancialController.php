<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialController extends Controller
{
    public function index(Request $request): Response
    {
        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $query = Lot::with(['project', 'client', 'status'])
            ->when($statusLibre, fn ($q) => $q->where('lot_status_id', '!=', $statusLibre->id));

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }
        if ($request->filled('start_date')) {
            $query->where('contract_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('contract_date', '<=', $request->input('end_date'));
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('id', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($q2) => $q2->where('name', 'like', "%{$term}%")
                        ->orWhere('dni', 'like', "%{$term}%"));
            });
        }

        $lots = $query->orderBy('contract_date', 'desc')->get();
        $totalValue = $lots->sum('price');
        $totalCollected = $lots->sum('advance');
        $totalPending = $totalValue - $totalCollected;
        $projects = Project::orderBy('name')->get();

        return Inertia::render('inmopro/financial', [
            'lots' => $lots,
            'projects' => $projects,
            'totalValue' => $totalValue,
            'totalCollected' => $totalCollected,
            'totalPending' => $totalPending,
            'filters' => $request->only('project_id', 'start_date', 'end_date', 'search'),
        ]);
    }
}
