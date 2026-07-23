<?php

namespace App\Actions\Pages;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListPages
{
    public function handle(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Page::class)
            ->with('updatedBy:id,uuid,name')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('slug'),
            ])
            ->allowedSorts(...['title', 'slug', 'updated_at', 'status'])
            ->defaultSort('-updated_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();
    }
}
