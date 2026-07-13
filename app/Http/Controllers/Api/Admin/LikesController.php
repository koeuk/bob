<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Likes
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class LikesController extends Controller
{
    /**
     * List likes
     *
     * @queryParam filter[type] string Exact type. Example: like
     * @queryParam filter[target] string Likeable type class. Example: App\Models\Post
     * @queryParam filter[user_uuid] string UUID of the user who liked. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @queryParam sort string One of: `created_at`, `-created_at`, `type`. Example: -created_at
     * @queryParam per_page int Results per page (default 50). Example: 50
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": { "data": [], "total": 0 },
     *   "counts": { "all": 0, "today": 0 }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $likes = QueryBuilder::for(Like::class)
            ->with(['user:id,uuid,name', 'likeable'])
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('target', 'likeable_type'),
                AllowedFilter::callback('user_uuid', function ($q, $value) {
                    $q->whereHas('user', fn ($u) => $u->where('uuid', $value));
                }),
            ])
            ->allowedSorts(['created_at', 'type'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return response()->json([
            'data' => $likes,
            'counts' => [
                'all' => Like::count(),
                'today' => Like::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    /**
     * Delete like
     *
     * @urlParam like string required Like UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Like removed." }
     */
    public function destroy(Like $like): JsonResponse
    {
        ActivityLog::record('like.delete', $like, $like->only(['user_id', 'likeable_type', 'likeable_id', 'type']));
        $like->delete();

        return response()->json(['message' => 'Like removed.']);
    }
}
