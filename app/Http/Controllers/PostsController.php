<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostsController extends Controller
{
    public function mine(Request $request): Response
    {
        $posts = Post::with('user:id,uuid,name')
            ->where('user_id', $request->user()->id)
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('posts/mine', [
            'posts' => $posts,
        ]);
    }

    public function show(Request $request, Post $post): Response
    {
        abort_if($post->status === 'hidden' && $post->user_id !== $request->user()->id, 404);

        $post->load(['user:id,uuid,name']);
        $post->loadCount(['likes']);

        $comments = Comment::with('user:id,uuid,name')
            ->where('post_id', $post->id)
            ->withCount('likes')
            ->latest()
            ->get();

        $userId = $request->user()->id;

        $likedPost = Like::where('user_id', $userId)
            ->where('likeable_type', Post::class)
            ->where('likeable_id', $post->id)
            ->exists();

        $likedCommentIds = Like::where('user_id', $userId)
            ->where('likeable_type', Comment::class)
            ->whereIn('likeable_id', $comments->pluck('id'))
            ->pluck('likeable_id')
            ->all();

        $comments->transform(function (Comment $c) use ($likedCommentIds) {
            $c->liked_by_me = in_array($c->id, $likedCommentIds, true);
            return $c;
        });

        $post->liked_by_me = $likedPost;

        return Inertia::render('posts/show', [
            'post' => $post,
            'comments' => $comments,
            'isAuthor' => $post->user_id === $userId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'status' => 'active',
        ]);

        return redirect()->route('posts.show', $post)->with('status', 'Posted.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $post->delete();

        return redirect()->route('feed')->with('status', 'Post deleted.');
    }

    public function like(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->status === 'hidden', 404);

        $user = $request->user();
        $existing = Like::where('user_id', $user->id)
            ->where('likeable_type', Post::class)
            ->where('likeable_id', $post->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Post::class,
                'likeable_id' => $post->id,
                'type' => 'like',
            ]);
        }

        return back();
    }
}
