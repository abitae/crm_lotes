<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\StoreAgendaEventRequest;
use App\Http\Requests\Api\v1\Cazador\UpdateAgendaEventRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorAgendaEvent;
use App\Models\Inmopro\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $query = AdvisorAgendaEvent::query()
            ->with('client:id,name')
            ->where('advisor_id', $advisor->id)
            ->orderBy('starts_at');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        if ($request->filled('start')) {
            $query->where('starts_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->where('starts_at', '<=', $request->input('end'));
        }

        $events = $query->get();

        return response()->json([
            'data' => $events->map(fn (AdvisorAgendaEvent $event): array => $this->eventPayload($event))->all(),
        ]);
    }

    public function store(StoreAgendaEventRequest $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $client = Client::query()
            ->whereKey($request->integer('client_id'))
            ->where('advisor_id', $advisor->id)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'El cliente no pertenece al vendedor autenticado.',
            ], 422);
        }

        $event = AdvisorAgendaEvent::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
        ])->load('client');

        return response()->json([
            'message' => 'Evento de agenda creado.',
            'data' => $this->eventPayload($event),
        ], 201);
    }

    public function show(Request $request, AdvisorAgendaEvent $agendaEvent): JsonResponse
    {
        $event = $this->ownedEvent($request, $agendaEvent);
        if (! $event) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }

        return response()->json([
            'data' => $this->eventPayload($event->load('client')),
        ]);
    }

    public function update(UpdateAgendaEventRequest $request, AdvisorAgendaEvent $agendaEvent): JsonResponse
    {
        $event = $this->ownedEvent($request, $agendaEvent);
        if (! $event) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }

        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');
        $client = Client::query()
            ->whereKey($request->integer('client_id'))
            ->where('advisor_id', $advisor->id)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'El cliente no pertenece al vendedor autenticado.',
            ], 422);
        }

        $event->update([
            'client_id' => $client->id,
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
        ]);

        return response()->json([
            'message' => 'Evento actualizado.',
            'data' => $this->eventPayload($event->fresh('client')),
        ]);
    }

    public function destroy(Request $request, AdvisorAgendaEvent $agendaEvent): JsonResponse
    {
        $event = $this->ownedEvent($request, $agendaEvent);
        if (! $event) {
            return response()->json(['message' => 'Evento no encontrado.'], 404);
        }
        $event->delete();

        return response()->json(['message' => 'Evento eliminado.'], 200);
    }

    private function ownedEvent(Request $request, AdvisorAgendaEvent $agendaEvent): ?AdvisorAgendaEvent
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        return AdvisorAgendaEvent::query()
            ->with('client:id,name')
            ->whereKey($agendaEvent->id)
            ->where('advisor_id', $advisor->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(AdvisorAgendaEvent $event): array
    {
        return [
            'id' => $event->id,
            'client_id' => $event->client_id,
            'client' => $event->client ? [
                'id' => $event->client->id,
                'name' => $event->client->name,
            ] : null,
            'title' => $event->title,
            'notes' => $event->notes,
            'starts_at' => $event->starts_at?->toAtomString(),
            'ends_at' => $event->ends_at?->toAtomString(),
            'created_at' => $event->created_at?->toAtomString(),
        ];
    }
}
