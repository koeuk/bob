<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Posts
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class PostsController extends Controller
{
    /**
     * List posts
     *
     * @queryParam filter[status] string Exact status. One of: `active`, `flagged`, `hidden`. Example: flagged
     * @queryParam filter[search] string Partial match on post body. Example: hello
     * @queryParam sort string One of: `created_at`, `-created_at`, `status`. Example: -created_at
     * @queryParam per_page int Results per page (default 25). Example: 25
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{ "id": 1, "uuid": "...", "body": "...", "status": "active", "comments_count": 2, "likes_count": 3, "reports_count": 0 }],
     *   "total": 83
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $posts = QueryBuilder::for(Post::class)
            ->with('user:id,uuid,name')
            ->withCount(['comments', 'likes', 'reports'])
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::partial('search', 'body'),
            ])
            ->allowedSorts(...['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json($posts);
    }

    /**
     * Get post
     *
     * Returns the post with its author, up to 50 comments, and all reports.
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "id": 1, "body": "...", "status": "active", "likes_count": 3, "user": {}, "comments": [], "reports": [] }
     */
    public function show(Post $post): JsonResponse
    {
        $post->load([
            'user:id,uuid,name',
            'comments' => fn ($q) => $q->with('user:id,uuid,name')->latest()->limit(50),
            'reports' => fn ($q) => $q->with('reporter:id,uuid,name')->latest(),
        ])->loadCount(['likes']);

        return response()->json($post);
    }

    /**
     * Create post
     *
     * @bodyParam body string required Post content (max 5,000 chars). Example: Admin-created post.
     * @bodyParam status string required One of: `active`, `flagged`, `hidden`. Example: active
     * @bodyParam user_uuid string UUID of the author. Defaults to the authenticated admin. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     *
     * @response 201 { "id": 85, "uuid": "...", "body": "Admin-created post.", "status": "active" }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:active,flagged,hidden'],
            'user_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
        ]);

        $authorId = isset($data['user_uuid'])
            ? User::where('uuid', $data['user_uuid'])->value('id')
            : $request->user()->id;

        $post = Post::create([
            'user_id' => $authorId,
            'body' => $data['body'],
            'status' => $data['status'],
        ]);

        ActivityLog::record('post.create', $post, null, $post->only(['body', 'status', 'user_id']));

        return response()->json($post->load('user:id,uuid,name'), 201);
    }

    /**
     * Update post
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam body string Post content (max 5,000 chars). Example: Updated body.
     * @bodyParam status string One of: `active`, `flagged`, `hidden`. Example: active
     * @bodyParam user_uuid string Reassign author UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam reason string Optional reason for the change (logged). Example: Corrected spam.
     *
     * @response 200 { "id": 1, "body": "Updated body.", "status": "active" }
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'body' => ['sometimes', 'string', 'max:5000'],
            'status' => ['sometimes', 'in:active,flagged,hidden'],
            'user_uuid' => ['sometimes', 'uuid', 'exists:users,uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = $post->only(['body', 'status', 'user_id']);

        $attrs = [];
        if (array_key_exists('body', $data)) $attrs['body'] = $data['body'];
        if (array_key_exists('status', $data)) $attrs['status'] = $data['status'];
        if (array_key_exists('user_uuid', $data)) $attrs['user_id'] = User::where('uuid', $data['user_uuid'])->value('id');

        $post->update($attrs);

        ActivityLog::record('post.update', $post, $before, [...$post->only(['body', 'status', 'user_id']), 'reason' => $data['reason'] ?? null]);

        return response()->json($post->fresh());
    }

    /**
     * Delete post
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Post deleted." }
     */
    public function destroy(Post $post): JsonResponse
    {
        ActivityLog::record('post.delete', $post, $post->only(['body', 'status']));
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    /**
     * Flag post
     *
     * Change a post's moderation status without editing its content.
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam status string required One of: `active`, `flagged`, `hidden`. Example: flagged
     *
     * @response 200 { "id": 1, "status": "flagged" }
     */
    public function flag(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,flagged,hidden'],
        ]);

        $before = ['status' => $post->status];
        $post->update(['status' => $data['status']]);

        ActivityLog::record('post.flag', $post, $before, ['status' => $data['status']]);

        return response()->json($post->fresh());
    }
}
