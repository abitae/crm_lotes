<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorLevelRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorLevelRequest;
use App\Models\Inmopro\AdvisorLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdvisorLevelController extends Controller
{
    public function index(): Response
    {
        $advisorLevels = AdvisorLevel::withCount('advisors')->orderBy('sort_order')->get();

        return Inertia::render('inmopro/advisor-levels/index', [
            'advisorLevels' => $advisorLevels,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inmopro/advisor-levels/create');
    }

    public function store(StoreAdvisorLevelRequest $request): RedirectResponse
    {
        AdvisorLevel::create($request->validated());

        return redirect()->route('inmopro.advisor-levels.index');
    }

    public function show(AdvisorLevel $advisor_level): Response
    {
        $advisor_level->loadCount('advisors');

        return Inertia::render('inmopro/advisor-levels/show', [
            'advisorLevel' => $advisor_level,
        ]);
    }

    public function edit(AdvisorLevel $advisor_level): Response
    {
        return Inertia::render('inmopro/advisor-levels/edit', [
            'advisorLevel' => $advisor_level,
        ]);
    }

    public function update(UpdateAdvisorLevelRequest $request, AdvisorLevel $advisor_level): RedirectResponse
    {
        $advisor_level->update($request->validated());

        return redirect()->route('inmopro.advisor-levels.index');
    }

    public function destroy(AdvisorLevel $advisor_level): RedirectResponse
    {
        $advisor_level->delete();

        return redirect()->route('inmopro.advisor-levels.index');
    }
}
