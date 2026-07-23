<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Likes\DeleteLike;
use App\Actions\Likes\ListLikes;
use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LikesController extends Controller
{
    public function index(Request $request, ListLikes $listLikes): Response
    {
        ['likes' => $likes, 'counts' => $counts] = $listLikes->handle($request);

        return Inertia::render('admin/likes/index', [
            'likes' => $likes,
            'filters' => $request->only(['filter']),
            'counts' => $counts,
        ]);
    }

    public function destroy(Like $like, DeleteLike $deleteLike): RedirectResponse
    {
        $deleteLike->handle($like);

        return back()->with('status', 'Like removed.');
    }
}
