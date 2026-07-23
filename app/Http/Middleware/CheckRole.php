<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'Required role: '.implode('|', $roles));
        }

        // EnsureNotBanned (global) normally handles this first; kept as
        // defence in depth. Log out via the session guard explicitly — the
        // sanctum RequestGuard has no logout(), so calling auth()->logout()
        // on an API request threw BadMethodCallException (a 500) instead of
        // returning the intended 403.
        if ($user->isBanned()) {
            if (! $request->expectsJson()) {
                auth('web')->logout();
            }

            abort(403, 'Account is banned.');
        }

        return $next($request);
    }
}
