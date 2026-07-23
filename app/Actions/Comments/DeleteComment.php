<?php

namespace App\Actions\Comments;

use App\Models\ActivityLog;
use App\Models\Comment;

class DeleteComment
{
    public function handle(Comment $comment): void
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));

        $comment->delete();
    }
}
