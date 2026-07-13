<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentsController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->isVisibleTo($request->user()), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            // Scoped existence check: the parent must be a comment on this post.
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('post_id', $post->id)],
        ]);

        Comment::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comment posted.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }

    public function like(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();
        $existing = Like::where('user_id', $user->id)
            ->where('likeable_type', Comment::class)
            ->where('likeable_id', $comment->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => Comment::class,
                'likeable_id' => $comment->id,
                'type' => 'like',
            ]);
        }

        return back();
    }
}
