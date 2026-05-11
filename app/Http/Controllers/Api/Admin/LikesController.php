<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LikesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $likes = QueryBuilder::for(Like::class)
            ->with(['user:id,uuid,name', 'likeable'])
            ->allowedFilters(...[
                AllowedFilter::exact('type'),
                AllowedFilter::exact('target', 'likeable_type'),
                AllowedFilter::callback('user_uuid', function ($q, $value) {
                    $q->whereHas('user', fn ($u) => $u->where('uuid', $value));
                }),
            ])
            ->allowedSorts(...['created_at', 'type'])
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

    public function destroy(Like $like): JsonResponse
    {
        ActivityLog::record('like.delete', $like, $like->only(['user_id', 'likeable_type', 'likeable_id', 'type']));
        $like->delete();

        return response()->json(['message' => 'Like removed.']);
    }
}
