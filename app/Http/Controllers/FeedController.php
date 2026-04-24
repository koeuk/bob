<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $posts = Post::with('user:id,uuid,name')
            ->where('status', 'active')
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Attach a liked flag per post for the current viewer (single query).
        $ids = collect($posts->items())->pluck('id')->all();
        $likedIds = Like::where('user_id', $userId)
            ->where('likeable_type', Post::class)
            ->whereIn('likeable_id', $ids)
            ->pluck('likeable_id')
            ->all();

        $posts->getCollection()->transform(function (Post $p) use ($likedIds) {
            $p->liked_by_me = in_array($p->id, $likedIds, true);
            return $p;
        });

        return Inertia::render('feed', [
            'posts' => $posts,
        ]);
    }
}
