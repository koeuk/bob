<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user) {
            $cacheKey = 'user_active_' . $user->id;
            if (! Cache::has($cacheKey)) {
                $user->timestamps = false;
                $user->updateQuietly(['last_active_at' => now()]);
                Cache::put($cacheKey, true, 60); // throttle to once per minute
            }
        }

        return $next($request);
    }
}
