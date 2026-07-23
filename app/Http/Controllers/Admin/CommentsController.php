<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comments\CreateComment;
use App\Actions\Comments\DeleteComment;
use App\Actions\Comments\ListComments;
use App\Actions\Comments\UpdateComment;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentsController extends Controller
{
    public function index(Request $request, ListComments $listComments): Response
    {
        return Inertia::render('admin/comments/index', [
            'comments' => $listComments->handle($request),
            'filters' => $request->only(['filter']),
            // Dropdown data for the admin composer — panel-only, so it stays here.
            'posts' => $this->postOptions(),
            'authors' => $this->authorOptions(),
        ]);
    }

    public function store(Request $request, CreateComment $createComment): RedirectResponse
    {
        $data = $request->validate(CreateComment::rules());

        $createComment->handle($data, $request->user());

        return back()->with('status', 'Comment posted.');
    }

    public function update(Request $request, Comment $comment, UpdateComment $updateComment): RedirectResponse
    {
        $data = $request->validate(UpdateComment::rules());

        $updateComment->handle($comment, $data);

        return back()->with('status', 'Comment updated.');
    }

    public function destroy(Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        $deleteComment->handle($comment);

        return back()->with('status', 'Comment deleted.');
    }

    private function postOptions()
    {
        return Post::select('id', 'uuid', 'body')
            ->where('status', '!=', 'hidden')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($p) => [
                'uuid' => $p->uuid,
                'preview' => mb_strlen($p->body) > 80 ? mb_substr($p->body, 0, 80).'…' : $p->body,
            ]);
    }

    private function authorOptions()
    {
        return User::select('id', 'uuid', 'name', 'email')
            ->with('roles')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->each->append('role');
    }
}
