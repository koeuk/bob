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
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Forbidden. Required role: '.implode('|', $roles)], 403);
        }

        if ($user->isBanned()) {
            return response()->json(['message' => 'Account is banned.'], 403);
        }

        return $next($request);
    }
}
