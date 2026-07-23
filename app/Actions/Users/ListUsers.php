<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Builds the paginated admin user list (search / role / banned filters).
 *
 * Shared by the Inertia admin panel and the JSON admin API so both surfaces
 * expose exactly the same columns, filters and sorts.
 */
class ListUsers
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $users = QueryBuilder::for(User::class)
            ->select(['id', 'uuid', 'name', 'email', 'avatar', 'created_at', 'email_verified_at'])
            ->withCount(['posts', 'comments'])
            ->with(['roles', 'bans' => fn ($q) => $q->active()])
            ->allowedFilters(...[
                AllowedFilter::callback('search', function ($q, $value) {
                    $q->where(function ($inner) use ($value) {
                        $inner->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('role', function ($q, $value) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', $value));
                }),
                AllowedFilter::callback('banned', function ($q, $value) {
                    if ($value) {
                        $q->whereHas('bans', fn ($b) => $b->active());
                    }
                }),
            ])
            ->allowedSorts(...['name', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        $users->getCollection()->each->append('role');

        return $users;
    }
}
