<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Feed & Posts
 */
class FeedController extends Controller
{
    /**
     * Feed
     *
     * Paginated list of all active posts, newest first. Each post includes a `liked_by_me` flag.
     *
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1, "uuid": "...", "body": "Hello world", "status": "active",
     *       "comments_count": 3, "likes_count": 5, "liked_by_me": false,
     *       "user": { "id": 1, "uuid": "...", "name": "Jane Doe" }
     *     }
     *   ],
     *   "last_page": 6, "per_page": 15, "total": 83
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user('sanctum')?->id;

        $seed = (int) $request->query('seed', rand(1, 999999));

        $posts = Post::with('user:id,uuid,name,avatar')
            ->where('status', 'active')
            ->where(function ($q) use ($userId) {
                $q->where('visibility', 'public');
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->withCount(['comments', 'likes'])
            ->orderByRaw('RAND(' . $seed . ')')
            ->paginate(15)
            ->withQueryString();

        $ids = collect($posts->items())->pluck('id')->all();
        $myLikes = $userId
            ? Like::where('user_id', $userId)
                ->where('likeable_type', Post::class)
                ->whereIn('likeable_id', $ids)
                ->get(['likeable_id', 'type'])
                ->keyBy('likeable_id')
            : collect();

        $posts->getCollection()->transform(function (Post $p) use ($myLikes) {
            $like = $myLikes->get($p->id);
            $p->liked_by_me = (bool) $like;
            $p->my_reaction = $like?->type;
            return $p;
        });

        return response()->json($posts);
    }
}
