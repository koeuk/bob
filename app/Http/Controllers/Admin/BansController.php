<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BansController extends Controller
{
    public function index(Request $request): Response
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

        // Users eligible for banning: not super_admin, not currently banned
        $bannableUsers = User::select('id', 'uuid', 'name', 'email', 'role')
            ->where('role', '!=', 'super_admin')
            ->whereDoesntHave('bans', fn ($q) => $q->active())
            ->orderBy('name')
            ->limit(500)
            ->get();

        return Inertia::render('admin/bans/index', [
            'bans' => $bans,
            'filters' => $request->only(['filter']),
            'counts' => [
                'all' => Ban::count(),
                'active' => Ban::active()->count(),
            ],
            'bannableUsers' => $bannableUsers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_uuid' => ['required', 'uuid', 'exists:users,uuid'],
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::where('uuid', $data['user_uuid'])->firstOrFail();

        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $request->user()->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $user->tokens()?->delete();

        ActivityLog::record('ban.create', $user, null, $ban->only(['reason', 'expires_at']));

        return back()->with('status', 'Ban created.');
    }

    public function destroy(Ban $ban): RedirectResponse
    {
        $ban->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('ban.remove', $ban->user);

        return back()->with('status', 'Ban lifted.');
    }
}
