<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Paginated admin post list (moderation view). */
class ListPosts
{
    use \App\Actions\Concerns\PaginatesLists;

    public function handle(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Post::class)
            ->with('user:id,uuid,name')
            ->withCount(['comments', 'likes', 'reports'])
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::partial('search', 'body'),
            ])
            ->allowedSorts(...['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();
    }

    /** Eager-loads everything the admin post-detail screen needs. */
    public function loadDetail(Post $post): Post
    {
        return $post->load([
            'user:id,uuid,name',
            'comments' => fn ($q) => $q->with('user:id,uuid,name')->latest()->limit(50),
            'reports' => fn ($q) => $q->with('reporter:id,uuid,name')->latest(),
        ])->loadCount(['likes']);
    }
}
