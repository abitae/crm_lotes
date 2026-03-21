<?php

namespace App\Http\Middleware;

use App\Models\Inmopro\DateroApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDateroApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if (! $header) {
            return $this->unauthorizedResponse();
        }

        $token = DateroApiToken::query()
            ->with(['datero.assignedAdvisor'])
            ->where('token', hash('sha256', $header))
            ->first();

        $datero = $token?->datero;
        $advisor = $datero?->assignedAdvisor;

        if (
            ! $token
            || ($token->expires_at !== null && $token->expires_at->isPast())
            || ! $datero?->is_active
            || $datero->advisor_id === null
            || ! $advisor?->is_active
        ) {
            return $this->unauthorizedResponse();
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('datero_token', $token);
        $request->attributes->set('datero', $datero);

        return $next($request);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'No autenticado.',
        ], 401);
    }
}
