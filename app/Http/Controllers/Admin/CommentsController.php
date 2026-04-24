<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CommentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $comments = QueryBuilder::for(Comment::class)
            ->with(['user:id,uuid,name', 'post:id,uuid,body'])
            ->withCount(['likes', 'reports'])
            ->allowedFilters([
                AllowedFilter::partial('search', 'body'),
                AllowedFilter::callback('post_uuid', function ($q, $value) {
                    $q->whereHas('post', fn ($p) => $p->where('uuid', $value));
                }),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($comments);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
