<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CommentsController extends Controller
{
    public function index(Request $request): Response
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
            ->paginate($request->integer('per_page', 30))
            ->withQueryString();

        return Inertia::render('admin/comments/index', [
            'comments' => $comments,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
