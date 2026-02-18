<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $lots = Lot::with(['project', 'status'])->get();
        $projects = Project::with('lots')->get();

        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $statusReservado = LotStatus::where('code', 'RESERVADO')->first();
        $statusTransferido = LotStatus::where('code', 'TRANSFERIDO')->first();

        $stats = [
            'total' => $lots->count(),
            'libre' => $statusLibre ? $lots->where('lot_status_id', $statusLibre->id)->count() : 0,
            'reservado' => $statusReservado ? $lots->where('lot_status_id', $statusReservado->id)->count() : 0,
            'transferido' => $statusTransferido ? $lots->where('lot_status_id', $statusTransferido->id)->count() : 0,
        ];

        $chartData = $projects->map(fn (Project $p) => [
            'name' => $p->name,
            'Libre' => $p->lots->where('lot_status_id', $statusLibre?->id)->count(),
            'Reservado' => $p->lots->where('lot_status_id', $statusReservado?->id)->count(),
            'Vendido' => $p->lots->where('lot_status_id', $statusTransferido?->id)->count(),
        ]);

        $pieData = [
            ['name' => 'Libre', 'value' => $stats['libre']],
            ['name' => 'Reservado', 'value' => $stats['reservado']],
            ['name' => 'Transferido', 'value' => $stats['transferido']],
        ];

        $recentReservations = Lot::with(['project', 'status', 'client', 'advisor'])
            ->where('lot_status_id', $statusReservado?->id)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return Inertia::render('inmopro/dashboard', [
            'stats' => $stats,
            'chartData' => $chartData,
            'pieData' => $pieData,
            'recentReservations' => $recentReservations,
        ]);
    }
}
