<?php

namespace App\Actions\Likes;

use App\Models\ActivityLog;
use App\Models\Like;

class DeleteLike
{
    public function handle(Like $like): void
    {
        ActivityLog::record('like.delete', $like, $like->only(['user_id', 'likeable_type', 'likeable_id', 'type']));

        $like->delete();
    }
}
