<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreDateroRequest;
use App\Http\Requests\Inmopro\UpdateDateroRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Datero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DateroController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Datero::query()->with(['assignedAdvisor:id,name', 'city']);

        if ($request->filled('search')) {
            $term = (string) $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('dni', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            });
        }

        $dateros = $query->orderBy('name')->paginate(15)->withQueryString();

        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'department']);

        $advisors = Advisor::query()->orderBy('name')->get(['id', 'name']);

        $dateroForModal = null;
        if ($request->filled('modal') && $request->input('modal') === 'edit_datero' && $request->filled('datero_id')) {
            $dateroForModal = Datero::query()
                ->with(['assignedAdvisor:id,name', 'city'])
                ->find($request->integer('datero_id'));
        }

        return Inertia::render('inmopro/dateros/index', [
            'dateros' => $dateros,
            'cities' => $cities,
            'advisors' => $advisors,
            'dateroForModal' => $dateroForModal,
            'openModal' => $request->input('modal'),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inmopro.dateros.index', ['modal' => 'create_datero']);
    }

    public function store(StoreDateroRequest $request): RedirectResponse
    {
        Datero::create($request->validated());

        return redirect()->route('inmopro.dateros.index')->with('success', 'Datero registrado correctamente.');
    }

    public function show(Datero $datero): RedirectResponse
    {
        return redirect()->route('inmopro.dateros.index', [
            'modal' => 'edit_datero',
            'datero_id' => $datero->id,
        ]);
    }

    public function edit(Datero $datero): RedirectResponse
    {
        return redirect()->route('inmopro.dateros.index', [
            'modal' => 'edit_datero',
            'datero_id' => $datero->id,
        ]);
    }

    public function update(UpdateDateroRequest $request, Datero $datero): RedirectResponse
    {
        $validated = $request->validated();
        if (empty($validated['pin'])) {
            unset($validated['pin']);
        }

        $datero->update($validated);

        return redirect()->route('inmopro.dateros.index')->with('success', 'Datero actualizado correctamente.');
    }

    public function destroy(Datero $datero): RedirectResponse
    {
        $datero->delete();

        return redirect()->route('inmopro.dateros.index')->with('success', 'Datero eliminado.');
    }
}
