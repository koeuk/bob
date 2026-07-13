<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Comments
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class CommentsController extends Controller
{
    /**
     * List comments
     *
     * @queryParam filter[search] string Partial match on comment body. Example: great
     * @queryParam filter[post_uuid] string Filter by post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @queryParam sort string One of: `created_at`, `-created_at`. Example: -created_at
     * @queryParam per_page int Results per page (default 30). Example: 30
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{ "id": 1, "body": "...", "likes_count": 0, "reports_count": 0, "user": {}, "post": {} }],
     *   "total": 210
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $comments = QueryBuilder::for(Comment::class)
            ->with(['user:id,uuid,name', 'post:id,uuid,body'])
            ->withCount(['likes', 'reports'])
            ->allowedFilters(...[
                AllowedFilter::partial('search', 'body'),
                AllowedFilter::callback('post_uuid', function ($q, $value) {
                    $q->whereHas('post', fn ($p) => $p->where('uuid', $value));
                }),
            ])
            ->allowedSorts(...['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 30))
            ->withQueryString();

        return response()->json($comments);
    }

    /**
     * Create comment
     *
     * @bodyParam post_uuid string required UUID of the post to comment on. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam body string required Comment text (max 2,000 chars). Example: Admin comment.
     * @bodyParam parent_uuid string UUID of parent comment for replies. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam user_uuid string Author UUID. Defaults to the authenticated admin. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     *
     * @response 201 { "id": 300, "body": "Admin comment.", "post_id": 1, "user_id": 2 }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_uuid' => ['required', 'uuid', 'exists:posts,uuid'],
            'body' => ['required', 'string', 'max:2000'],
            'parent_uuid' => ['nullable', 'uuid', 'exists:comments,uuid'],
            'user_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
        ]);

        $post = Post::where('uuid', $data['post_uuid'])->firstOrFail();
        $authorId = isset($data['user_uuid'])
            ? User::where('uuid', $data['user_uuid'])->value('id')
            : $request->user()->id;
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

        return response()->json($comment->load(['user:id,uuid,name', 'post:id,uuid']), 201);
    }

    /**
     * Update comment
     *
     * @urlParam comment string required Comment UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam body string required Updated comment text (max 2,000 chars). Example: Edited comment.
     * @bodyParam reason string Optional reason (logged). Example: Removed offensive language.
     *
     * @response 200 { "id": 1, "body": "Edited comment." }
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = $comment->only(['body']);
        $comment->update(['body' => $data['body']]);

        ActivityLog::record('comment.update', $comment, $before, ['body' => $data['body'], 'reason' => $data['reason'] ?? null]);

        return response()->json($comment->fresh());
    }

    /**
     * Delete comment
     *
     * @urlParam comment string required Comment UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Comment deleted." }
     */
    public function destroy(Comment $comment): JsonResponse
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
