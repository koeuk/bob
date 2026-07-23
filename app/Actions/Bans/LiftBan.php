<?php

namespace App\Actions\Bans;

use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Lifts a single ban by expiring it. Distinct from Users\UnbanUser, which
 * expires *every* active ban a user holds.
 *
 * The gate lives here (as it does in IssueBan) so both the Inertia and JSON
 * callers are covered. Without it this route was a hierarchy bypass: a
 * moderator denied `POST /admin/users/{admin}/unban` could still lift that
 * same ban via `DELETE /admin/bans/{uuid}`.
 */
class LiftBan
{
    public function handle(Ban $ban, User $actor): void
    {
        Gate::forUser($actor)->authorize('unban', $ban->user);

        $ban->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('ban.remove', $ban->user);
    }
}
