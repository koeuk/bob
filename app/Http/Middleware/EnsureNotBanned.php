<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks banned users from the whole authenticated app.
 *
 * Previously `isBanned()` was only consulted by CheckRole (mounted on the
 * staff route groups) and the API login, so a ban closed the admin panel and
 * revoked API tokens but left the web app wide open: the banned user simply
 * logged back in and kept posting. Guests pass straight through.
 */
class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isBanned()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            // Revoke tokens so the client cannot keep retrying with this one.
            $user->tokens()?->delete();

            return response()->json(['message' => 'Account is banned.'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Your account has been banned.',
        ]);
    }
}
