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

        if ($user->isBanned()) {
            auth()->logout();
            abort(403, 'Account is banned.');
        }

        return $next($request);
    }
}
