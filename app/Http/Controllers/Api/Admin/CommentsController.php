<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Comments\CreateComment;
use App\Actions\Comments\DeleteComment;
use App\Actions\Comments\ListComments;
use App\Actions\Comments\UpdateComment;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request, ListComments $listComments): JsonResponse
    {
        return response()->json($listComments->handle($request));
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
    public function store(Request $request, CreateComment $createComment): JsonResponse
    {
        $data = $request->validate(CreateComment::rules());

        $comment = $createComment->handle($data, $request->user());

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
    public function update(Request $request, Comment $comment, UpdateComment $updateComment): JsonResponse
    {
        $data = $request->validate(UpdateComment::rules());

        $updateComment->handle($comment, $data);

        return response()->json($comment->fresh());
    }

    /**
     * Delete comment
     *
     * @urlParam comment string required Comment UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Comment deleted." }
     */
    public function destroy(Comment $comment, DeleteComment $deleteComment): JsonResponse
    {
        $deleteComment->handle($comment);

        return response()->json(['message' => 'Comment deleted.']);
    }
}
