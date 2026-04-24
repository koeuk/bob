<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()
            ->with('user:id,uuid,name')
            ->withCount(['comments', 'likes', 'reports']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where('body', 'like', "%{$search}%");
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to);
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 25))
        );
    }

    public function show(Post $post): JsonResponse
    {
        $post->load([
            'user:id,uuid,name',
            'comments' => fn ($q) => $q->with('user:id,uuid,name')->latest()->limit(50),
            'reports' => fn ($q) => $q->with('reporter:id,uuid,name')->latest(),
        ])->loadCount(['likes']);

        return response()->json(['post' => $post]);
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

        return response()->json(['post' => $post]);
    }
}
