<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mpdf\Mpdf;

class ReportController extends Controller
{
    /**
     * @return array{globalSold: float, globalGoal: float, globalPct: int, levelSold: float, levelGoal: float, levelPct: int, levelAdvisorsCount: int, sellersPerformance: array<int, array{id: int, name: string, full: string, Logrado: float, Meta: float, pct: int}>}
     */
    private function reportData(Request $request, ?int &$selectedLevel = null): array
    {
        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $levels = AdvisorLevel::orderBy('sort_order')->get();
        $selectedLevel = $request->input('level_id') && $levels->contains('id', (int) $request->input('level_id'))
            ? (int) $request->input('level_id')
            : $levels->first()?->id;

        $advisors = Advisor::with('level')->get();
        $lots = Lot::when($statusLibre, fn ($q) => $q->where('lot_status_id', '!=', $statusLibre->id))->get();

        $globalSold = (float) $lots->sum('price');
        $globalGoal = (float) $advisors->sum('personal_quota');
        $globalPct = $globalGoal > 0 ? (int) round(($globalSold / $globalGoal) * 100) : 0;

        $levelAdvisors = $advisors->where('advisor_level_id', $selectedLevel);
        $levelSold = (float) $lots->whereIn('advisor_id', $levelAdvisors->pluck('id'))->sum('price');
        $levelGoal = (float) $levelAdvisors->sum('personal_quota');
        $levelPct = $levelGoal > 0 ? (int) round(($levelSold / $levelGoal) * 100) : 0;

        $sellersPerformance = $levelAdvisors->map(function (Advisor $adv) use ($lots) {
            $advLots = $lots->where('advisor_id', $adv->id);
            $achieved = (float) $advLots->sum('price');
            $goal = (float) $adv->personal_quota;
            $pct = $goal > 0 ? (int) round(($achieved / $goal) * 100) : 0;

            return [
                'id' => $adv->id,
                'name' => $adv->name,
                'full' => $adv->name,
                'Logrado' => $achieved,
                'Meta' => $goal,
                'pct' => $pct,
            ];
        })->sortByDesc('Logrado')->values()->all();

        return [
            'globalSold' => $globalSold,
            'globalGoal' => $globalGoal,
            'globalPct' => $globalPct,
            'levelSold' => $levelSold,
            'levelGoal' => $levelGoal,
            'levelPct' => $levelPct,
            'levelAdvisorsCount' => $levelAdvisors->count(),
            'sellersPerformance' => $sellersPerformance,
        ];
    }

    public function index(Request $request): InertiaResponse
    {
        $selectedLevel = null;
        $data = $this->reportData($request, $selectedLevel);
        $levels = AdvisorLevel::orderBy('sort_order')->get();

        return Inertia::render('inmopro/reports', [
            'advisorLevels' => $levels,
            'selectedLevelId' => $selectedLevel,
            ...$data,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $selectedLevel = null;
        $data = $this->reportData($request, $selectedLevel);
        $level = AdvisorLevel::find($selectedLevel);
        $levelName = $level?->name ?? 'Todos';

        $html = View::make('inmopro.report-pdf', [
            ...$data,
            'levelName' => $levelName,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-ventas-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }
}
