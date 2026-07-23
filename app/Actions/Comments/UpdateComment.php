<?php

namespace App\Actions\Comments;

use App\Models\ActivityLog;
use App\Models\Comment;

class UpdateComment
{
    public static function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function handle(Comment $comment, array $data): Comment
    {
        $before = $comment->only(['body']);

        $comment->update(['body' => $data['body']]);

        ActivityLog::record('comment.update', $comment, $before, [
            'body' => $data['body'],
            'reason' => $data['reason'] ?? null,
        ]);

        return $comment;
    }
}
