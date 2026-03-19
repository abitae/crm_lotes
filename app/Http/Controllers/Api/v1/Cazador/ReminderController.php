<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\StoreReminderRequest;
use App\Http\Requests\Api\v1\Cazador\UpdateReminderRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorReminder;
use App\Models\Inmopro\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $query = AdvisorReminder::query()
            ->with('client:id,name')
            ->where('advisor_id', $advisor->id)
            ->orderBy('remind_at');

        if ($request->boolean('pending_only')) {
            $query->pending();
        }

        $reminders = $query->get();

        return response()->json([
            'data' => $reminders->map(fn (AdvisorReminder $reminder): array => $this->reminderPayload($reminder))->all(),
        ]);
    }

    public function store(StoreReminderRequest $request): JsonResponse
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

        $reminder = AdvisorReminder::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
            'remind_at' => $request->input('remind_at'),
        ])->load('client');

        return response()->json([
            'message' => 'Recordatorio creado.',
            'data' => $this->reminderPayload($reminder),
        ], 201);
    }

    public function show(Request $request, AdvisorReminder $reminder): JsonResponse
    {
        $owned = $this->ownedReminder($request, $reminder);
        if (! $owned) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
        }

        return response()->json([
            'data' => $this->reminderPayload($owned->load('client')),
        ]);
    }

    public function update(UpdateReminderRequest $request, AdvisorReminder $reminder): JsonResponse
    {
        $owned = $this->ownedReminder($request, $reminder);
        if (! $owned) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
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

        $owned->update([
            'client_id' => $client->id,
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
            'remind_at' => $request->input('remind_at'),
        ]);

        return response()->json([
            'message' => 'Recordatorio actualizado.',
            'data' => $this->reminderPayload($owned->fresh('client')),
        ]);
    }

    public function destroy(Request $request, AdvisorReminder $reminder): JsonResponse
    {
        $owned = $this->ownedReminder($request, $reminder);
        if (! $owned) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
        }
        $owned->delete();

        return response()->json(['message' => 'Recordatorio eliminado.'], 200);
    }

    public function complete(Request $request, AdvisorReminder $reminder): JsonResponse
    {
        $owned = $this->ownedReminder($request, $reminder);
        if (! $owned) {
            return response()->json(['message' => 'Recordatorio no encontrado.'], 404);
        }
        $owned->update(['completed_at' => now()]);

        return response()->json([
            'message' => 'Recordatorio marcado como realizado.',
            'data' => $this->reminderPayload($owned->fresh('client')),
        ]);
    }

    private function ownedReminder(Request $request, AdvisorReminder $reminder): ?AdvisorReminder
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        return AdvisorReminder::query()
            ->with('client:id,name')
            ->whereKey($reminder->id)
            ->where('advisor_id', $advisor->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function reminderPayload(AdvisorReminder $reminder): array
    {
        return [
            'id' => $reminder->id,
            'client_id' => $reminder->client_id,
            'client' => $reminder->client ? [
                'id' => $reminder->client->id,
                'name' => $reminder->client->name,
            ] : null,
            'title' => $reminder->title,
            'notes' => $reminder->notes,
            'remind_at' => $reminder->remind_at?->toAtomString(),
            'completed_at' => $reminder->completed_at?->toAtomString(),
            'created_at' => $reminder->created_at?->toAtomString(),
        ];
    }
}
