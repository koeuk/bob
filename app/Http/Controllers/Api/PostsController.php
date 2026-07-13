<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Notifications\PostLiked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Feed & Posts
 */
class PostsController extends Controller
{
    /**
     * My posts
     *
     * Full list of the authenticated user's own posts, newest first (not paginated).
     *
     * @response 200 [
     *   { "id": 1, "uuid": "...", "body": "...", "status": "active", "comments_count": 2, "likes_count": 1 }
     * ]
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = Post::with(['user:id,uuid,name,avatar', 'sharedPost.user:id,uuid,name,avatar'])
            ->where('user_id', $user->id)
            ->withCount(['comments', 'likes', 'shares'])
            ->latest()
            ->get();

        $postIds = $posts->pluck('id')->all();

        $likedMap = Like::where('user_id', $user->id)
            ->where('likeable_type', Post::class)
            ->whereIn('likeable_id', $postIds)
            ->pluck('type', 'likeable_id');

        $reactionSummaries = empty($postIds) ? collect() : Like::where('likeable_type', Post::class)
            ->whereIn('likeable_id', $postIds)
            ->selectRaw('likeable_id, type, count(*) as count')
            ->groupBy('likeable_id', 'type')
            ->get()
            ->groupBy('likeable_id')
            ->map(fn ($rows) => $rows->pluck('count', 'type')->toArray());

        $posts->transform(function (Post $post) use ($likedMap, $reactionSummaries) {
            $post->my_reaction = $likedMap->get($post->id);
            $post->liked_by_me = $likedMap->has($post->id);
            $post->reactions_summary = $reactionSummaries->get($post->id, []);
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Get post
     *
     * Return a single post with all its comments. Hidden posts are only visible to their author.
     *
     * @urlParam post string required The post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 {
     *   "post": { "id": 1, "uuid": "...", "body": "...", "status": "active", "likes_count": 3, "liked_by_me": false },
     *   "comments": [{ "id": 1, "body": "Nice post!", "liked_by_me": false, "user": { "name": "Bob" } }],
     *   "is_author": false
     * }
     * @response 404 { "message": "Not Found." }
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $userId = $viewer?->id;

        abort_unless($post->isVisibleTo($viewer), 404);

        $post->load(['user:id,uuid,name,avatar', 'sharedPost.user:id,uuid,name,avatar']);
        $post->loadCount(['likes', 'comments', 'shares']);

        $comments = Comment::with([
                'user:id,uuid,name,avatar',
                'replies' => fn($q) => $q->with('user:id,uuid,name,avatar')->withCount('likes'),
            ])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->withCount('likes')
            ->latest()
            ->get();

        if ($userId) {
            $postLike = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->first();

            $allCommentIds = $comments->flatMap(
                fn($c) => $c->replies->pluck('id')->prepend($c->id)
            )->all();

            $myCommentLikes = Like::where('user_id', $userId)
                ->where('likeable_type', Comment::class)
                ->whereIn('likeable_id', $allCommentIds)
                ->get(['likeable_id', 'type'])
                ->keyBy('likeable_id');
        } else {
            $postLike = null;
            $myCommentLikes = collect();
        }

        $comments->transform(function (Comment $c) use ($myCommentLikes) {
            $like = $myCommentLikes->get($c->id);
            $c->liked_by_me = (bool) $like;
            $c->my_reaction = $like?->type;
            $c->replies->transform(function (Comment $r) use ($myCommentLikes) {
                $rLike = $myCommentLikes->get($r->id);
                $r->liked_by_me = (bool) $rLike;
                $r->my_reaction = $rLike?->type;
                return $r;
            });
            return $c;
        });

        $post->liked_by_me = (bool) $postLike;
        $post->my_reaction = $postLike?->type;
        $post->reactions_summary = Like::where('likeable_type', Post::class)
            ->where('likeable_id', $post->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'post' => $post,
            'comments' => $comments,
            'is_author' => $post->user_id === $userId,
        ]);
    }

    /**
     * Create post
     *
     * @bodyParam body string required Post content (max 10,000 characters). Example: Hello everyone!
     *
     * @response 201 { "id": 84, "uuid": "...", "body": "Hello everyone!", "status": "active" }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body'           => ['nullable', 'string', 'max:10000'],
            'images'         => ['nullable', 'array', 'max:10'],
            'images.*'       => ['image', 'max:5120'],
            'feeling'        => ['nullable', 'string', 'max:50'],
            'visibility'     => ['nullable', 'in:public,private'],
            'shared_post_id' => ['nullable', 'integer'],
        ]);

        $sharedPostId = $data['shared_post_id'] ?? null;

        // Only public, active posts may be shared — otherwise sharing a private
        // or moderator-hidden post would leak its content to everyone.
        if ($sharedPostId) {
            $shared = Post::find($sharedPostId);
            abort_if(
                ! $shared || $shared->visibility !== 'public' || $shared->status !== 'active',
                422,
                'This post cannot be shared.'
            );
        }

        abort_if(
            empty(trim($data['body'] ?? '')) && empty($request->file('images')) && !$sharedPostId,
            422
        );

        $imageUrls = [];
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('posts', 'public');
            $imageUrls[] = $path;
        }

        $post = Post::create([
            'user_id'        => $request->user()->id,
            'body'           => $data['body'] ?? null,
            'status'         => 'active',
            'images'         => $imageUrls ?: null,
            'image'          => $imageUrls[0] ?? null,
            'feeling'        => $data['feeling'] ?? null,
            'visibility'     => $data['visibility'] ?? 'public',
            'shared_post_id' => $sharedPostId,
        ]);

        $post->load(['user:id,uuid,name,avatar', 'sharedPost.user:id,uuid,name,avatar']);

        return response()->json($post, 201);
    }

    /**
     * Update post
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'body'            => ['nullable', 'string', 'max:10000'],
            'new_images'      => ['nullable', 'array', 'max:10'],
            'new_images.*'    => ['image', 'max:5120'],
            'keep_images'     => ['nullable', 'array'],
            'keep_images.*'   => ['string'],
            'feeling'         => ['nullable', 'string', 'max:50'],
            'visibility'      => ['nullable', 'in:public,private'],
        ]);

        $kept = $data['keep_images'] ?? [];

        $imageUrls = $kept;
        foreach ($request->file('new_images', []) as $file) {
            $path = $file->store('posts', 'public');
            $imageUrls[] = $path;
        }

        abort_if(empty(trim($data['body'] ?? '')) && empty($imageUrls), 422);

        $post->update([
            'body'       => $data['body'] ?? null,
            'images'     => $imageUrls ?: null,
            'image'      => $imageUrls[0] ?? null,
            'feeling'    => $data['feeling'] ?? null,
            'visibility' => $data['visibility'] ?? $post->visibility,
        ]);

        $post->load('user:id,uuid,name,avatar');

        return response()->json($post);
    }

    /**
     * Delete post
     *
     * Soft-delete your own post.
     *
     * @urlParam post string required The post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Post deleted." }
     * @response 403 { "message": "This action is unauthorized." }
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    /**
     * Toggle like on post
     *
     * Like or unlike a post. Returns the new liked state and updated count.
     *
     * @urlParam post string required The post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "liked": true, "likes_count": 6 }
     */
    public function like(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->isVisibleTo($request->user()), 404);

        $request->validate([
            'type' => ['nullable', 'string', 'in:like,love,haha,wow,sad,angry'],
        ]);

        $type = $request->input('type');
        $user = $request->user();

        $existing = Like::where('user_id', $user->id)
            ->where('likeable_type', Post::class)
            ->where('likeable_id', $post->id)
            ->first();

        if ($existing) {
            if ($type === null || $existing->type === $type) {
                $existing->delete();
                $myReaction = null;
            } else {
                $existing->update(['type' => $type]);
                $myReaction = $type;
            }
        } else {
            if ($type !== null) {
                Like::create([
                    'user_id'       => $user->id,
                    'likeable_type' => Post::class,
                    'likeable_id'   => $post->id,
                    'type'          => $type,
                ]);
                $myReaction = $type;

                // Notify post owner (skip self-reactions)
                if ($post->user_id !== $user->id) {
                    $post->user->notify(new PostLiked($user, $post, $type));
                }
            } else {
                $myReaction = null;
            }
        }

        return response()->json([
            'my_reaction' => $myReaction,
            'liked' => $myReaction !== null,
            'likes_count' => $post->likes()->count(),
        ]);
    }
}
