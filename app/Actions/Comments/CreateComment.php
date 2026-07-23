<?php

namespace App\Actions\Comments;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Admin comment creation: the author may be any user (defaults to the acting
 * admin) and the comment may be a reply to an existing comment.
 */
class CreateComment
{
    public static function rules(): array
    {
        return [
            'post_uuid' => ['required', 'uuid', 'exists:posts,uuid'],
            'body' => ['required', 'string', 'max:2000'],
            'parent_uuid' => ['nullable', 'uuid', 'exists:comments,uuid'],
            'user_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
        ];
    }

    public function handle(array $data, User $actor): Comment
    {
        $post = Post::where('uuid', $data['post_uuid'])->firstOrFail();

        $authorId = isset($data['user_uuid'])
            ? User::where('uuid', $data['user_uuid'])->value('id')
            : $actor->id;

        $parentId = isset($data['parent_uuid'])
            ? Comment::where('uuid', $data['parent_uuid'])->value('id')
            : null;

        $comment = Comment::create([
            'user_id' => $authorId,
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'body' => $data['body'],
        ]);

        ActivityLog::record('comment.create', $comment, null, $comment->only(['body', 'post_id', 'user_id', 'parent_id']));

        return $comment;
    }
}
