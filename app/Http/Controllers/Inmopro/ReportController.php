<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $statusLibre = LotStatus::where('code', 'LIBRE')->first();
        $levels = AdvisorLevel::orderBy('sort_order')->get();
        $selectedLevel = $request->input('level_id') && $levels->contains('id', (int) $request->input('level_id'))
            ? (int) $request->input('level_id')
            : $levels->first()?->id;

        $advisors = Advisor::with('level')->get();
        $lots = Lot::when($statusLibre, fn ($q) => $q->where('lot_status_id', '!=', $statusLibre->id))->get();

        $globalSold = $lots->sum('price');
        $globalGoal = $advisors->sum('personal_quota');
        $globalPct = $globalGoal > 0 ? (int) round(($globalSold / $globalGoal) * 100) : 0;

        $levelAdvisors = $advisors->where('advisor_level_id', $selectedLevel);
        $levelSold = $lots->whereIn('advisor_id', $levelAdvisors->pluck('id'))->sum('price');
        $levelGoal = $levelAdvisors->sum('personal_quota');
        $levelPct = $levelGoal > 0 ? (int) round(($levelSold / $levelGoal) * 100) : 0;

        $sellersPerformance = $levelAdvisors->map(function (Advisor $adv) use ($lots) {
            $advLots = $lots->where('advisor_id', $adv->id);
            $achieved = $advLots->sum('price');
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

        return Inertia::render('inmopro/reports', [
            'advisorLevels' => $levels,
            'selectedLevelId' => $selectedLevel,
            'globalSold' => $globalSold,
            'globalGoal' => $globalGoal,
            'globalPct' => $globalPct,
            'levelSold' => $levelSold,
            'levelGoal' => $levelGoal,
            'levelPct' => $levelPct,
            'levelAdvisorsCount' => $levelAdvisors->count(),
            'sellersPerformance' => $sellersPerformance,
        ]);
    }
}
