<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
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

        $seed   = (int) $request->query('seed', rand(1, 999999));
        $search = trim($request->query('q', ''));

        $posts = Post::with('user:id,uuid,name,avatar')
            ->where('status', 'active')
            ->where(function ($q) use ($userId) {
                $q->where('visibility', 'public');
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('body', 'like', $like)
                          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                });
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

        // Build friendship status map for post authors
        $friendMap = [];
        if ($userId) {
            $authorIds = collect($posts->items())
                ->pluck('user_id')
                ->filter(fn ($id) => $id !== $userId)
                ->unique()
                ->values()
                ->all();

            if (!empty($authorIds)) {
                $friendRequests = FriendRequest::where(function ($q) use ($userId, $authorIds) {
                    $q->where('sender_id', $userId)->whereIn('receiver_id', $authorIds);
                })->orWhere(function ($q) use ($userId, $authorIds) {
                    $q->where('receiver_id', $userId)->whereIn('sender_id', $authorIds);
                })->get();

                foreach ($friendRequests as $fr) {
                    if ($fr->sender_id === $userId) {
                        $otherId = $fr->receiver_id;
                        $status  = $fr->status === 'accepted' ? 'friends' : 'pending_sent';
                    } else {
                        $otherId = $fr->sender_id;
                        $status  = $fr->status === 'accepted' ? 'friends' : 'pending_received';
                    }
                    $friendMap[$otherId] = ['status' => $status, 'request_id' => $fr->id];
                }
            }
        }

        $posts->getCollection()->transform(function (Post $p) use ($myLikes, $userId, $friendMap) {
            $like = $myLikes->get($p->id);
            $p->liked_by_me = (bool) $like;
            $p->my_reaction = $like?->type;
            $p->friendship_status = ($userId && $p->user_id !== $userId)
                ? ($friendMap[$p->user_id] ?? ['status' => 'none'])
                : null;
            return $p;
        });

        return response()->json($posts);
    }
}
