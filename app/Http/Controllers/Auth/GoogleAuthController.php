<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5174'), '/');

        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google user',
                    'password' => Str::random(40),
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasAnyRole(['user', 'moderator', 'admin', 'super_admin'])) {
                $user->assignRole('user');
            }

            if ($user->isBanned()) {
                return redirect()->away($frontendUrl.'/auth/google/callback?error='.urlencode('Your account has been banned.'));
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $token = $user->createToken('google-auth')->plainTextToken;

            return redirect()->away($frontendUrl.'/auth/google/callback?token='.urlencode($token));
        } catch (Throwable $exception) {
            Log::warning('Google authentication failed.', ['exception' => $exception]);

            return redirect()->away($frontendUrl.'/auth/google/callback?error='.urlencode('Google sign-in failed. Please try again.'));
        }
    }
}
