<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\UpdateAdvisorPinRequest;
use App\Http\Requests\Api\v1\Cazador\UpdateAdvisorProfileRequest;
use App\Models\Inmopro\Advisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');
        $advisor->loadMissing(['team', 'level']);

        return response()->json([
            'data' => $this->advisorPayload($advisor),
        ]);
    }

    public function update(UpdateAdvisorProfileRequest $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');
        $request->attributes->set('advisor', $advisor);
        $advisor->update($request->validated());

        return response()->json([
            'message' => 'Perfil actualizado.',
            'data' => $this->advisorPayload($advisor->fresh(['team', 'level'])),
        ]);
    }

    public function updatePin(UpdateAdvisorPinRequest $request): JsonResponse
    {
        /** @var Advisor $advisor */
        $advisor = $request->attributes->get('advisor');

        if (! Hash::check((string) $request->input('current_pin'), (string) $advisor->pin)) {
            return response()->json([
                'message' => 'El PIN actual no es válido.',
            ], 422);
        }

        $advisor->update([
            'pin' => $request->input('pin'),
        ]);

        return response()->json([
            'message' => 'PIN actualizado correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function advisorPayload(Advisor $advisor): array
    {
        return [
            'id' => $advisor->id,
            'name' => $advisor->name,
            'phone' => $advisor->phone,
            'email' => $advisor->email,
            'username' => $advisor->username,
            'team' => $advisor->team ? [
                'id' => $advisor->team->id,
                'name' => $advisor->team->name,
            ] : null,
            'level' => $advisor->level ? [
                'id' => $advisor->level->id,
                'name' => $advisor->level->name,
            ] : null,
        ];
    }
}
