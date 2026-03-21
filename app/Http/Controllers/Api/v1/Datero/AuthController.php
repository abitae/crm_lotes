<?php

namespace App\Http\Controllers\Api\v1\Datero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Datero\LoginDateroRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Datero;
use App\Models\Inmopro\DateroApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginDateroRequest $request): JsonResponse
    {
        $datero = Datero::query()
            ->with(['assignedAdvisor.team', 'assignedAdvisor.level', 'city'])
            ->where('username', $request->string('username')->toString())
            ->first();

        $advisor = $datero?->assignedAdvisor;

        if (
            ! $datero
            || ! $datero->is_active
            || ! $datero->pin
            || ! Hash::check((string) $request->input('pin'), $datero->pin)
            || ! $advisor
            || ! $advisor->is_active
        ) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 422);
        }

        $issuedToken = DateroApiToken::issueFor($datero, (string) $request->input('device_name', 'Datero'));
        $datero->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $issuedToken['plain_text_token'],
            'datero' => $this->dateroPayload($datero->fresh(['city'])),
            'advisor' => $this->advisorPayload($advisor->fresh(['team', 'level'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('datero_token')?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
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
