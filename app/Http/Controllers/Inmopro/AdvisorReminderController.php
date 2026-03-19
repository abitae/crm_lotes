<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAdvisorReminderRequest;
use App\Http\Requests\Inmopro\UpdateAdvisorReminderRequest;
use App\Models\Inmopro\AdvisorReminder;
use Illuminate\Http\RedirectResponse;

class AdvisorReminderController extends Controller
{
    public function store(StoreAdvisorReminderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        AdvisorReminder::create([
            'advisor_id' => $validated['advisor_id'],
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'remind_at' => $validated['remind_at'],
        ]);

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $validated['advisor_id']])
            ->with('success', 'Recordatorio creado.');
    }

    public function update(UpdateAdvisorReminderRequest $request, AdvisorReminder $advisor_reminder): RedirectResponse
    {
        $validated = $request->validated();
        $advisor_reminder->update([
            'advisor_id' => $validated['advisor_id'],
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'remind_at' => $validated['remind_at'],
        ]);

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $validated['advisor_id']])
            ->with('success', 'Recordatorio actualizado.');
    }

    public function destroy(AdvisorReminder $advisor_reminder): RedirectResponse
    {
        $advisorId = $advisor_reminder->advisor_id;
        $advisor_reminder->delete();

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $advisorId])
            ->with('success', 'Recordatorio eliminado.');
    }

    public function complete(AdvisorReminder $advisor_reminder): RedirectResponse
    {
        $advisor_reminder->update(['completed_at' => now()]);

        return redirect()
            ->route('inmopro.agenda.index', ['advisor_id' => $advisor_reminder->advisor_id])
            ->with('success', 'Recordatorio marcado como realizado.');
    }
}
