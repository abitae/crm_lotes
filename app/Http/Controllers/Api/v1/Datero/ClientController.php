<?php

namespace App\Http\Controllers\Api\v1\Datero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Datero\StoreClientRequest;
use App\Http\Requests\Api\v1\Datero\UpdateClientRequest;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\Inmopro\Datero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Datero $datero */
        $datero = $request->attributes->get('datero');

        $clients = Client::query()
            ->with('city')
            ->where('registered_by_datero_id', $datero->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = (string) $request->input('search');
                $query->where(function ($nestedQuery) use ($term) {
                    $nestedQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('dni', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $clients->map(fn (Client $client) => $this->clientPayload($client))->all(),
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        /** @var Datero $datero */
        $datero = $request->attributes->get('datero');

        $dateroTypeId = ClientType::query()->where('code', 'DATERO')->value('id');
        if ($dateroTypeId === null) {
            throw new RuntimeException('Falta el tipo de cliente DATERO en la base de datos.');
        }

        $client = Client::create([
            ...$request->validated(),
            'advisor_id' => $datero->advisor_id,
            'client_type_id' => $dateroTypeId,
            'registered_by_datero_id' => $datero->id,
        ]);

        return response()->json([
            'message' => 'Cliente registrado.',
            'data' => $this->clientPayload($client->fresh('city')),
        ], 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $ownedClient = $this->ownedClient($request, $client);
        if ($ownedClient === null) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $ownedClient->load(['city', 'lots.project', 'lots.status']);

        return response()->json([
            'data' => $this->clientPayload($ownedClient, true),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $ownedClient = $this->ownedClient($request, $client);
        if ($ownedClient === null) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $ownedClient->update($request->validated());

        return response()->json([
            'message' => 'Cliente actualizado.',
            'data' => $this->clientPayload($ownedClient->fresh('city')),
        ]);
    }

    private function ownedClient(Request $request, Client $client): ?Client
    {
        /** @var Datero $datero */
        $datero = $request->attributes->get('datero');

        return Client::query()
            ->whereKey($client->id)
            ->where('registered_by_datero_id', $datero->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function clientPayload(Client $client, bool $includeLots = false): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'dni' => $client->dni,
            'phone' => $client->phone,
            'email' => $client->email,
            'referred_by' => $client->referred_by,
            'city' => $client->city ? [
                'id' => $client->city->id,
                'name' => $client->city->name,
                'department' => $client->city->department,
            ] : null,
            'lots' => $includeLots
                ? $client->lots->map(fn ($lot) => [
                    'id' => $lot->id,
                    'block' => $lot->block,
                    'number' => $lot->number,
                    'project' => $lot->project?->name,
                    'status' => $lot->status?->code,
                ])->all()
                : [],
        ];
    }
}
