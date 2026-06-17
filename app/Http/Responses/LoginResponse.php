<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Send moderators/admins to the admin panel; everyone else to the app.
     */
    public function toResponse($request)
    {
        $user = $request->user();

        $target = $user?->isModerator()
            ? '/admin/dashboard'
            : config('fortify.home', '/dashboard');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($target);
    }
}
