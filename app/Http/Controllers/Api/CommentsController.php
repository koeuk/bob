<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
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

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless(
            $comment->user_id === $request->user()->id || $request->user()->isModerator(),
            403,
        );

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

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
