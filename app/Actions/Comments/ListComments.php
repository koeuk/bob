<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Paginated admin comment list. Shared by the Inertia admin panel and the
 * JSON admin API.
 */
class ListComments
{
    public function handle(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Comment::class)
            ->with(['user:id,uuid,name', 'post:id,uuid,body'])
            ->withCount(['likes', 'reports'])
            ->allowedFilters(...[
                AllowedFilter::partial('search', 'body'),
                AllowedFilter::callback('post_uuid', function ($q, $value) {
                    $q->whereHas('post', fn ($p) => $p->where('uuid', $value));
                }),
            ])
            ->allowedSorts(...['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 30))
            ->withQueryString();
    }
}
