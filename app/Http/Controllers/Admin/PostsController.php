<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PostsController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = QueryBuilder::for(Post::class)
            ->with('user:id,uuid,name')
            ->withCount(['comments', 'likes', 'reports'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::partial('search', 'body'),
            ])
            ->allowedSorts(['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function show(Post $post): Response
    {
        $post->load([
            'user:id,uuid,name',
            'comments' => fn ($q) => $q->with('user:id,uuid,name')->latest()->limit(50),
            'reports' => fn ($q) => $q->with('reporter:id,uuid,name')->latest(),
        ])->loadCount(['likes']);

        return Inertia::render('admin/posts/show', [
            'post' => $post,
        ]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        ActivityLog::record('post.delete', $post, $post->only(['body', 'status']));
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Post deleted.');
    }

    public function flag(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,flagged,hidden'],
        ]);

        $before = ['status' => $post->status];
        $post->update(['status' => $data['status']]);

        ActivityLog::record('post.flag', $post, $before, ['status' => $data['status']]);

        return back()->with('status', 'Post status updated.');
    }
}
