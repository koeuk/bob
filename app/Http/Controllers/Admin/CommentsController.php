<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()
            ->with(['user:id,uuid,name', 'post:id,uuid,body'])
            ->withCount(['likes', 'reports']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where('body', 'like', "%{$search}%");
        }

        if ($postUuid = $request->string('post_uuid')->trim()->value()) {
            $query->whereHas('post', fn ($q) => $q->where('uuid', $postUuid));
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 25))
        );
    }

    public function destroy(Comment $comment): JsonResponse
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
