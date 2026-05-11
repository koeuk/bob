<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Comments
 */
class CommentsController extends Controller
{
    /**
     * Create comment
     *
     * Add a comment to a post. Supports nested replies via `parent_id`.
     *
     * @urlParam post string required The post UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam body string required Comment text (max 5,000 characters). Example: Great post!
     * @bodyParam parent_id int The ID of the parent comment for replies. Example: 5
     *
     * @response 201 { "id": 10, "uuid": "...", "body": "Great post!", "post_id": 1, "user_id": 2, "parent_id": null }
     * @response 404 { "message": "Not Found." }
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        abort_if($post->status === 'hidden', 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        $comment->load('user:id,uuid,name');

        return response()->json($comment, 201);
    }

    /**
     * Delete comment
     *
     * Delete your own comment. Moderators can delete any comment.
     *
     * @urlParam comment string required The comment UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Comment deleted." }
     * @response 403 { "message": "This action is unauthorized." }
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless(
            $comment->user_id === $request->user()->id || $request->user()->isModerator(),
            403,
        );

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * Toggle like on comment
     *
     * Like or unlike a comment. Returns the new liked state and updated count.
     *
     * @urlParam comment string required The comment UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "liked": true, "likes_count": 2 }
     */
    public function like(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();
        $existing = Like::where('user_id', $user->id)
            ->where('likeable_type', Comment::class)
            ->where('likeable_id', $comment->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Comment::class,
                'likeable_id' => $comment->id,
                'type' => 'like',
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $comment->likes()->count(),
        ]);
    }
}
