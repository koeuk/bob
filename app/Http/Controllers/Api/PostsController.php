<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
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
        $posts = Post::with('user:id,uuid,name')
            ->where('user_id', $request->user()->id)
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

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

        $post->load(['user:id,uuid,name']);
        $post->loadCount(['likes']);

        $comments = Comment::with('user:id,uuid,name')
            ->where('post_id', $post->id)
            ->withCount('likes')
            ->latest()
            ->get();

        if ($userId) {
            $likedPost = Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->where('likeable_id', $post->id)
                ->exists();

            $likedCommentIds = Like::where('user_id', $userId)
                ->where('likeable_type', Comment::class)
                ->whereIn('likeable_id', $comments->pluck('id'))
                ->pluck('likeable_id')
                ->all();
        } else {
            $likedPost = false;
            $likedCommentIds = [];
        }

        $comments->transform(function (Comment $c) use ($likedCommentIds) {
            $c->liked_by_me = in_array($c->id, $likedCommentIds, true);
            return $c;
        });

        $post->liked_by_me = $likedPost;

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
            'body'    => ['required', 'string', 'max:10000'],
            'image'   => ['nullable', 'image', 'max:5120'],
            'feeling' => ['nullable', 'string', 'max:50'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
            'status'  => 'active',
            'image'   => $imagePath ? url('storage/' . $imagePath) : null,
            'feeling' => $data['feeling'] ?? null,
        ]);

        $post->load('user:id,uuid,name');

        return response()->json($post, 201);
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

        $user = $request->user();
        $existing = Like::where('user_id', $user->id)
            ->where('likeable_type', Post::class)
            ->where('likeable_id', $post->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Post::class,
                'likeable_id' => $post->id,
                'type' => 'like',
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }
}
