<?php

namespace App\Http\Middleware;

use App\Models\Inmopro\AdvisorApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdvisorApiToken
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

        $token = AdvisorApiToken::query()
            ->with('advisor.team')
            ->where('token', hash('sha256', $header))
            ->first();

        if (! $token || ($token->expires_at !== null && $token->expires_at->isPast()) || ! $token->advisor?->is_active) {
            return $this->unauthorizedResponse();
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('advisor_token', $token);
        $request->attributes->set('advisor', $token->advisor);

        return $next($request);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'No autenticado.',
        ], 401);
    }
}
