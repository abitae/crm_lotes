<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInmoproRoutePermission
{
    /**
     * Require the authenticated user to have a Spatie permission whose name
     * matches the current route name (inmopro.*), except access-control routes.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $name = $request->route()?->getName();

        if (! is_string($name) || ! str_starts_with($name, 'inmopro.')) {
            return $next($request);
        }

        if (str_starts_with($name, 'inmopro.access-control.')) {
            return $next($request);
        }

        abort_unless($user && $user->can($name), 403);

        return $next($request);
    }
}
