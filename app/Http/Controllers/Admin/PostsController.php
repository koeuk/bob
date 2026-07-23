<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Posts\ListPosts;
use App\Actions\Posts\ManagePost;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostsController extends Controller
{
    public function index(Request $request, ListPosts $listPosts): Response
    {
        return Inertia::render('admin/posts/index', [
            'posts' => $listPosts->handle($request),
            'filters' => $request->only(['filter']),
        ]);
    }

    public function show(Post $post, ListPosts $listPosts): Response
    {
        return Inertia::render('admin/posts/show', [
            'post' => $listPosts->loadDetail($post),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/edit', [
            'post' => null,
            'authors' => $this->authorOptions(),
        ]);
    }

    public function store(Request $request, ManagePost $managePost): RedirectResponse
    {
        $data = $request->validate(ManagePost::createRules());

        $post = $managePost->create($data, $request->user());

        return redirect()->route('admin.posts.show', $post)->with('status', 'Post created.');
    }

    public function edit(Post $post): Response
    {
        $post->load('user:id,uuid,name,email');

        return Inertia::render('admin/posts/edit', [
            'post' => $post,
            'authors' => $this->authorOptions(),
        ]);
    }

    public function update(Request $request, Post $post, ManagePost $managePost): RedirectResponse
    {
        $data = $request->validate(ManagePost::updateRules());

        $managePost->update($post, $data);

        return back()->with('status', 'Post updated.');
    }

    public function destroy(Post $post, ManagePost $managePost): RedirectResponse
    {
        $managePost->delete($post);

        return redirect()->route('admin.posts.index')->with('status', 'Post deleted.');
    }

    public function flag(Request $request, Post $post, ManagePost $managePost): RedirectResponse
    {
        $data = $request->validate(ManagePost::statusRules());

        $managePost->setStatus($post, $data['status']);

        return back()->with('status', 'Post status updated.');
    }

    /** Author picker for the admin post composer — panel-only. */
    private function authorOptions()
    {
        return User::select('id', 'uuid', 'name', 'email')->orderBy('name')->limit(500)->get();
    }
}
