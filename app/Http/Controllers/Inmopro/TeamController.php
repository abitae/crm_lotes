<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreTeamRequest;
use App\Http\Requests\Inmopro\UpdateTeamRequest;
use App\Models\Inmopro\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $teams = Team::query()
            ->withCount('advisors')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inmopro/teams/index', [
            'teams' => $teams,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inmopro/teams/create');
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        Team::create($request->validated());

        return redirect()->route('inmopro.teams.index');
    }

    public function show(Team $team): Response
    {
        $team->loadCount('advisors');

        return Inertia::render('inmopro/teams/show', [
            'team' => $team,
        ]);
    }

    public function edit(Team $team): Response
    {
        return Inertia::render('inmopro/teams/edit', [
            'team' => $team,
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()->route('inmopro.teams.index');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()->route('inmopro.teams.index');
    }
}
