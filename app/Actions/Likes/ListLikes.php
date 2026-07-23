<?php

namespace App\Actions\Likes;

use App\Models\Like;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListLikes
{
    /** @return array{likes: \Illuminate\Contracts\Pagination\LengthAwarePaginator, counts: array{all:int, today:int}} */
    public function handle(Request $request): array
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

        return [
            'likes' => $likes,
            'counts' => [
                'all' => Like::count(),
                'today' => Like::whereDate('created_at', today())->count(),
            ],
        ];
    }
}
