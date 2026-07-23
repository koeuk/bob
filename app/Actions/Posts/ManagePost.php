<?php

namespace App\Actions\Posts;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;

/**
 * Admin post moderation: create / update / delete / set status.
 *
 * The admin may author a post on behalf of another user and may reassign a
 * post's author, which is why these differ from the user-facing post flow.
 */
class ManagePost
{
    public const STATUSES = 'in:active,flagged,hidden';

    public static function createRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', self::STATUSES],
            'user_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
        ];
    }

    public static function updateRules(): array
    {
        return [
            'body' => ['sometimes', 'string', 'max:5000'],
            'status' => ['sometimes', self::STATUSES],
            'user_uuid' => ['sometimes', 'uuid', 'exists:users,uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function statusRules(): array
    {
        return ['status' => ['required', self::STATUSES]];
    }

    public function create(array $data, User $actor): Post
    {
        $authorId = ! empty($data['user_uuid'])
            ? User::where('uuid', $data['user_uuid'])->value('id')
            : $actor->id;

        $post = Post::create([
            'user_id' => $authorId,
            'body' => $data['body'],
            'status' => $data['status'],
        ]);

        ActivityLog::record('post.create', $post, null, $post->only(['body', 'status', 'user_id']));

        return $post;
    }

    public function update(Post $post, array $data): Post
    {
        $before = $post->only(['body', 'status', 'user_id']);

        $attrs = [];
        if (array_key_exists('body', $data)) {
            $attrs['body'] = $data['body'];
        }
        if (array_key_exists('status', $data)) {
            $attrs['status'] = $data['status'];
        }
        if (array_key_exists('user_uuid', $data)) {
            $attrs['user_id'] = User::where('uuid', $data['user_uuid'])->value('id');
        }

        $post->update($attrs);

        ActivityLog::record('post.update', $post, $before, [
            ...$post->only(['body', 'status', 'user_id']),
            'reason' => $data['reason'] ?? null,
        ]);

        return $post;
    }

    public function delete(Post $post): void
    {
        ActivityLog::record('post.delete', $post, $post->only(['body', 'status']));

        $post->delete();
    }

    public function setStatus(Post $post, string $status): Post
    {
        $before = ['status' => $post->status];

        $post->update(['status' => $status]);

        ActivityLog::record('post.flag', $post, $before, ['status' => $status]);

        return $post;
    }
}
