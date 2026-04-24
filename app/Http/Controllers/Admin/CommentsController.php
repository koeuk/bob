<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
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

        $posts = Post::select('id', 'uuid', 'body')
            ->where('status', '!=', 'hidden')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($p) => [
                'uuid' => $p->uuid,
                'preview' => mb_strlen($p->body) > 80 ? mb_substr($p->body, 0, 80).'…' : $p->body,
            ]);

        $authors = User::select('id', 'uuid', 'name', 'email', 'role')
            ->orderBy('name')
            ->limit(500)
            ->get();

        return Inertia::render('admin/comments/index', [
            'comments' => $comments,
            'filters' => $request->only(['filter']),
            'posts' => $posts,
            'authors' => $authors,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        return back()->with('status', 'Comment posted.');
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = $comment->only(['body']);
        $comment->update(['body' => $data['body']]);

        ActivityLog::record('comment.update', $comment, $before, ['body' => $data['body'], 'reason' => $data['reason'] ?? null]);

        return back()->with('status', 'Comment updated.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        ActivityLog::record('comment.delete', $comment, $comment->only(['body']));
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
