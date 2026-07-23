<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;

class DeleteUser
{
    public function handle(User $user): void
    {
        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));

        $user->delete();
    }
}
