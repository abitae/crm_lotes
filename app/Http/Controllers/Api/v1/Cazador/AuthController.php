<?php

namespace App\Http\Controllers\Api\v1\Cazador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Cazador\LoginAdvisorRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginAdvisorRequest $request): JsonResponse
    {
        $advisor = Advisor::query()
            ->with(['team', 'level'])
            ->where('username', $request->string('username')->toString())
            ->first();

        if (! $advisor || ! $advisor->is_active || ! $advisor->pin || ! Hash::check((string) $request->input('pin'), $advisor->pin)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 422);
        }

        $issuedToken = AdvisorApiToken::issueFor($advisor, (string) $request->input('device_name', 'Cazador'));
        $advisor->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $issuedToken['plain_text_token'],
            'advisor' => $this->advisorPayload($advisor->fresh(['team', 'level'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('advisor_token')?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
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
