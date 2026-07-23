<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Posts\ListPosts;
use App\Actions\Posts\ManagePost;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request, ListPosts $listPosts): JsonResponse
    {
        return response()->json($listPosts->handle($request));
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
    public function show(Post $post, ListPosts $listPosts): JsonResponse
    {
        return response()->json($listPosts->loadDetail($post));
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
    public function store(Request $request, ManagePost $managePost): JsonResponse
    {
        $data = $request->validate(ManagePost::createRules());

        $post = $managePost->create($data, $request->user());

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
    public function update(Request $request, Post $post, ManagePost $managePost): JsonResponse
    {
        $data = $request->validate(ManagePost::updateRules());

        $managePost->update($post, $data);

        return response()->json($post->fresh());
    }

    /**
     * Delete post
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Post deleted." }
     */
    public function destroy(Post $post, ManagePost $managePost): JsonResponse
    {
        $managePost->delete($post);

        return response()->json(['message' => 'Post deleted.']);
    }

    /**
     * Flag post
     *
     * Sets the moderation status without touching the body.
     *
     * @urlParam post string required Post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam status string required One of: `active`, `flagged`, `hidden`. Example: flagged
     *
     * @response 200 { "id": 1, "status": "flagged" }
     */
    public function flag(Request $request, Post $post, ManagePost $managePost): JsonResponse
    {
        $data = $request->validate(ManagePost::statusRules());

        $managePost->setStatus($post, $data['status']);

        return response()->json($post->fresh());
    }
}
