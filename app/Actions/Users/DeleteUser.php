<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DeleteUser
{
    public function handle(User $user): void
    {
        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));

        // Read the raw columns: the accessors return /storage/... URLs, which
        // never match a disk path. UpdateUser already cleans up on *replacement*
        // but deletion left the files behind forever.
        foreach (['avatar', 'cover'] as $column) {
            if ($path = $user->getRawOriginal($column)) {
                Storage::disk('public')->delete($path);
            }
        }

        $user->delete();
    }
}
