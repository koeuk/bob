<?php

namespace App\Actions\Bans;

use App\Models\Ban;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListBans
{
    /** @return array{bans: \Illuminate\Contracts\Pagination\LengthAwarePaginator, counts: array{all:int, active:int}} */
    public function handle(Request $request): array
    {
        $bans = QueryBuilder::for(Ban::class)
            ->with(['user:id,uuid,name,email', 'bannedBy:id,uuid,name'])
            ->allowedFilters(...[
                AllowedFilter::callback('search', function ($q, $value) {
                    $q->whereHas('user', function ($u) use ($value) {
                        $u->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('active', function ($q, $value) {
                    if ($value) {
                        $q->active();
                    }
                }),
            ])
            ->allowedSorts(...['created_at', 'expires_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return [
            'bans' => $bans,
            'counts' => [
                'all' => Ban::count(),
                'active' => Ban::active()->count(),
            ],
        ];
    }
}
