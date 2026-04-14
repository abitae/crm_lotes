<?php

namespace App\Http\Controllers\Api\v1\Datero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Datero\UpdateDateroPinRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Datero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Datero $datero */
        $datero = $request->attributes->get('datero');
        $datero->loadMissing(['city', 'assignedAdvisor.team', 'assignedAdvisor.level']);
        $advisor = $datero->assignedAdvisor;

        return response()->json([
            'data' => [
                'datero' => $this->dateroPayload($datero),
                'advisor' => $advisor ? $this->advisorPayload($advisor) : null,
            ],
        ]);
    }

    public function updatePin(UpdateDateroPinRequest $request): JsonResponse
    {
        /** @var Datero $datero */
        $datero = $request->attributes->get('datero');

        if (! Hash::check((string) $request->input('current_pin'), (string) $datero->pin)) {
            return response()->json([
                'message' => 'El PIN actual no es válido.',
            ], 422);
        }

        $datero->update([
            'pin' => $request->input('pin'),
        ]);

        return response()->json([
            'message' => 'PIN actualizado correctamente.',
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function advisorPayload(Advisor $advisor): array
    {
        return [
            'id' => $advisor->id,
            'name' => $advisor->name,
            'first_name' => $advisor->first_name,
            'last_name' => $advisor->last_name,
            'birth_date' => $advisor->birth_date?->toDateString(),
            'phone' => $advisor->phone,
            'email' => $advisor->email,
            'username' => $advisor->username,
            'is_active' => $advisor->is_active,
            'team' => $advisor->team ? [
                'id' => $advisor->team->id,
                'name' => $advisor->team->name,
                'color' => $advisor->team->color,
            ] : null,
            'level' => $advisor->level ? [
                'id' => $advisor->level->id,
                'name' => $advisor->level->name,
                'code' => $advisor->level->code,
            ] : null,
        ];
    }
}
