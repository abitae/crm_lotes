<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorAgendaEventRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorAgendaEventRequest;
use App\Models\Inmopro\AdvisorAgendaEvent;
use Illuminate\Http\RedirectResponse;

class AdvisorAgendaEventController extends Controller
{
    public function store(StoreAdvisorAgendaEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        AdvisorAgendaEvent::create([
            'advisor_id' => $validated['advisor_id'],
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $validated['advisor_id']])
            ->with('success', 'Evento de agenda creado.');
    }

    public function update(UpdateAdvisorAgendaEventRequest $request, AdvisorAgendaEvent $advisor_agenda_event): RedirectResponse
    {
        $validated = $request->validated();
        $advisor_agenda_event->update([
            'advisor_id' => $validated['advisor_id'],
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $validated['advisor_id']])
            ->with('success', 'Evento actualizado.');
    }

    public function destroy(AdvisorAgendaEvent $advisor_agenda_event): RedirectResponse
    {
        $advisorId = $advisor_agenda_event->advisor_id;
        $advisor_agenda_event->delete();

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $advisorId])
            ->with('success', 'Evento eliminado.');
    }
}
