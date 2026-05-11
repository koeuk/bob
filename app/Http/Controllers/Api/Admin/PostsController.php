<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PostsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = QueryBuilder::for(Post::class)
            ->with('user:id,uuid,name')
            ->withCount(['comments', 'likes', 'reports'])
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::partial('search', 'body'),
            ])
            ->allowedSorts(...['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json($posts);
    }

    public function show(Post $post): JsonResponse
    {
        $post->load([
            'user:id,uuid,name',
            'comments' => fn ($q) => $q->with('user:id,uuid,name')->latest()->limit(50),
            'reports' => fn ($q) => $q->with('reporter:id,uuid,name')->latest(),
        ])->loadCount(['likes']);

        return response()->json($post);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:active,flagged,hidden'],
            'user_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
        ]);

        $authorId = isset($data['user_uuid'])
            ? User::where('uuid', $data['user_uuid'])->value('id')
            : $request->user()->id;

        $post = Post::create([
            'user_id' => $authorId,
            'body' => $data['body'],
            'status' => $data['status'],
        ]);

        ActivityLog::record('post.create', $post, null, $post->only(['body', 'status', 'user_id']));

        return response()->json($post->load('user:id,uuid,name'), 201);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'body' => ['sometimes', 'string', 'max:5000'],
            'status' => ['sometimes', 'in:active,flagged,hidden'],
            'user_uuid' => ['sometimes', 'uuid', 'exists:users,uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = $post->only(['body', 'status', 'user_id']);

        $attrs = [];
        if (array_key_exists('body', $data)) $attrs['body'] = $data['body'];
        if (array_key_exists('status', $data)) $attrs['status'] = $data['status'];
        if (array_key_exists('user_uuid', $data)) $attrs['user_id'] = User::where('uuid', $data['user_uuid'])->value('id');

        $post->update($attrs);

        ActivityLog::record('post.update', $post, $before, [...$post->only(['body', 'status', 'user_id']), 'reason' => $data['reason'] ?? null]);

        return response()->json($post->fresh());
    }

    public function destroy(Post $post): JsonResponse
    {
        ActivityLog::record('post.delete', $post, $post->only(['body', 'status']));
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    public function flag(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,flagged,hidden'],
        ]);

        $before = ['status' => $post->status];
        $post->update(['status' => $data['status']]);

        ActivityLog::record('post.flag', $post, $before, ['status' => $data['status']]);

        return response()->json($post->fresh());
    }
}
