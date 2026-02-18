<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdvisorController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $term = trim((string) $q);
        if ($term === '') {
            return response()->json([]);
        }
        $like = '%'.$term.'%';
        $advisors = Advisor::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json($advisors);
    }

    public function index(Request $request): Response
    {
        $query = Advisor::with(['level', 'superior'])->withCount('lots');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $advisors = $query->orderBy('name')->paginate(20)->withQueryString();
        $advisorLevels = AdvisorLevel::orderBy('sort_order')->get();

        return Inertia::render('inmopro/advisors/index', [
            'advisors' => $advisors,
            'advisorLevels' => $advisorLevels,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $advisorLevels = AdvisorLevel::orderBy('sort_order')->get();
        $advisors = Advisor::orderBy('name')->get(['id', 'name']);

        return Inertia::render('inmopro/advisors/create', [
            'advisorLevels' => $advisorLevels,
            'advisors' => $advisors,
        ]);
    }

    public function store(StoreAdvisorRequest $request): RedirectResponse
    {
        Advisor::create($request->validated());

        return redirect()->route('inmopro.advisors.index');
    }

    public function show(Advisor $advisor): Response
    {
        $advisor->load(['level', 'superior', 'lots.project', 'lots.status']);

        return Inertia::render('inmopro/advisors/show', [
            'advisor' => $advisor,
        ]);
    }

    public function edit(Advisor $advisor): Response
    {
        $advisor->load('level');
        $advisorLevels = AdvisorLevel::orderBy('sort_order')->get();
        $advisors = Advisor::where('id', '!=', $advisor->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('inmopro/advisors/edit', [
            'advisor' => $advisor,
            'advisorLevels' => $advisorLevels,
            'advisors' => $advisors,
        ]);
    }

    public function update(UpdateAdvisorRequest $request, Advisor $advisor): RedirectResponse
    {
        $advisor->update($request->validated());

        return redirect()->route('inmopro.advisors.index');
    }
}
