<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LikesController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('admin/likes/index', [
            'likes' => $likes,
            'filters' => $request->only(['filter']),
            'counts' => [
                'all' => Like::count(),
                'today' => Like::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function destroy(Like $like): RedirectResponse
    {
        ActivityLog::record('like.delete', $like, $like->only(['user_id', 'likeable_type', 'likeable_id', 'type']));
        $like->delete();

        return back()->with('status', 'Like removed.');
    }
}
