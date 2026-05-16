<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Notifications\PostLiked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @group Feed & Posts
 */
class PostsController extends Controller
{
    /**
     * My posts
     *
     * Paginated list of the authenticated user's own posts, newest first.
     *
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [{ "id": 1, "uuid": "...", "body": "...", "status": "active", "comments_count": 2, "likes_count": 1 }],
     *   "last_page": 1, "per_page": 20, "total": 14
     * }
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = Post::with('user:id,uuid,name,avatar')
            ->where('user_id', $user->id)
            ->withCount(['comments', 'likes'])
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
        $userId = $request->user('sanctum')?->id;

        abort_if($post->status === 'hidden' && $post->user_id !== ($userId ?? -1), 404);
        abort_if($post->visibility === 'private' && $post->user_id !== ($userId ?? -1), 404);

        $post->load(['user:id,uuid,name,avatar']);
        $post->loadCount(['likes']);

        $comments = Comment::with('user:id,uuid,name,avatar')
            ->where('post_id', $post->id)
            ->withCount('likes')
            ->latest()
            ->get();

        if ($userId) {
            $postLike = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->first();

            $likedCommentIds = Like::where('user_id', $userId)
                ->where('likeable_type', Comment::class)
                ->whereIn('likeable_id', $comments->pluck('id'))
                ->pluck('likeable_id')
                ->all();
        } else {
            $postLike = null;
            $likedCommentIds = [];
        }

        $comments->transform(function (Comment $c) use ($likedCommentIds) {
            $c->liked_by_me = in_array($c->id, $likedCommentIds, true);
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
            'body'       => ['nullable', 'string', 'max:10000'],
            'images'     => ['nullable', 'array', 'max:10'],
            'images.*'   => ['image', 'max:5120'],
            'feeling'    => ['nullable', 'string', 'max:50'],
            'visibility' => ['nullable', 'in:public,private'],
        ]);

        abort_if(empty(trim($data['body'] ?? '')) && empty($request->file('images')), 422);

        $imageUrls = [];
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('posts', 'public');
            $imageUrls[] = $path;
        }

        $post = Post::create([
            'user_id'    => $request->user()->id,
            'body'       => $data['body'] ?? null,
            'status'     => 'active',
            'images'     => $imageUrls ?: null,
            'image'      => $imageUrls[0] ?? null,
            'feeling'    => $data['feeling'] ?? null,
            'visibility' => $data['visibility'] ?? 'public',
        ]);

        $post->load('user:id,uuid,name,avatar');

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
        abort_if($post->status === 'hidden', 404);

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
            'likes_count' => $post->likes()->count(),
        ]);
    }
}
