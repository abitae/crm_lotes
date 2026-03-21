<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\StoreDateroRequest;
use App\Http\Requests\Api\v1\Cazador\UpdateDateroRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Datero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DateroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $dateros = Datero::query()
            ->with('city')
            ->where('advisor_id', $advisor->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = (string) $request->input('search');
                $query->where(function ($nested) use ($term) {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('dni', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $dateros->map(fn (Datero $datero) => $this->dateroPayload($datero))->all(),
        ]);
    }

    public function store(StoreDateroRequest $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        $datero = Datero::create([
            ...$request->validated(),
            'advisor_id' => $advisor->id,
        ]);

        return response()->json([
            'message' => 'Datero registrado.',
            'data' => $this->dateroPayload($datero->fresh('city')),
        ], 201);
    }

    public function update(UpdateDateroRequest $request, Datero $datero): JsonResponse
    {
        $owned = $this->ownedDatero($request, $datero);
        if ($owned === null) {
            return response()->json(['message' => 'Datero no encontrado.'], 404);
        }

        $validated = $request->validated();
        if (empty($validated['pin'])) {
            unset($validated['pin']);
        }

        $owned->update($validated);

        return response()->json([
            'message' => 'Datero actualizado.',
            'data' => $this->dateroPayload($owned->fresh('city')),
        ]);
    }

    private function ownedDatero(Request $request, Datero $datero): ?Datero
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        return Datero::query()
            ->whereKey($datero->id)
            ->where('advisor_id', $advisor->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function dateroPayload(Datero $datero): array
    {
        return [
            'id' => $datero->id,
            'name' => $datero->name,
            'phone' => $datero->phone,
            'email' => $datero->email,
            'dni' => $datero->dni,
            'username' => $datero->username,
            'is_active' => $datero->is_active,
            'last_login_at' => $datero->last_login_at?->toIso8601String(),
            'city' => $datero->city ? [
                'id' => $datero->city->id,
                'name' => $datero->city->name,
                'department' => $datero->city->department,
            ] : null,
        ];
    }
}
