<?php

namespace App\Actions\Bans;

use App\Models\ActivityLog;
use App\Models\Ban;

/**
 * Lifts a single ban by expiring it. Distinct from Users\UnbanUser, which
 * expires *every* active ban a user holds.
 */
class LiftBan
{
    public function handle(Ban $ban): void
    {
        $ban->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('ban.remove', $ban->user);
    }
}
