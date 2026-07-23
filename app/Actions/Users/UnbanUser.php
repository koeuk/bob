<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;

class UnbanUser
{
    public function handle(User $user): void
    {
        $user->bans()->active()->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('user.unban', $user);
    }
}
