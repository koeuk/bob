<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::class)
            ->select(['id', 'uuid', 'name', 'email', 'role', 'created_at', 'email_verified_at'])
            ->withCount(['posts', 'comments'])
            ->with(['bans' => fn ($q) => $q->active()])
            ->allowedFilters([
                AllowedFilter::callback('search', function ($q, $value) {
                    $q->where(function ($inner) use ($value) {
                        $inner->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::exact('role'),
                AllowedFilter::callback('banned', function ($q, $value) {
                    if ($value) {
                        $q->whereHas('bans', fn ($b) => $b->active());
                    }
                }),
            ])
            ->allowedSorts(['name', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['filter', 'sort', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ]);

        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can create admin-level users.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        ActivityLog::record('user.create', $user, null, $user->only(['name', 'email', 'role']));

        return redirect()->route('admin.users.show', $user)->with('status', 'User created.');
    }

    public function show(User $user): Response
    {
        $user->load([
            'bans' => fn ($q) => $q->with('bannedBy:id,uuid,name')->latest(),
        ])->loadCount(['posts', 'comments', 'reportsFiled']);

        $reportsAgainst = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->with('reporter:id,uuid,name')
            ->latest()
            ->limit(20)
            ->get();

        $activity = ActivityLog::where(function ($q) use ($user) {
            $q->where('admin_id', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', User::class)->where('target_id', $user->id);
                });
        })
            ->with('admin:id,uuid,name')
            ->latest()
            ->limit(30)
            ->get();

        return Inertia::render('admin/users/show', [
            'user' => $user,
            'reportsAgainst' => $reportsAgainst,
            'activity' => $activity,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $before = $user->only(array_keys($data));
        $user->update($data);

        ActivityLog::record('user.update', $user, $before, $user->only(array_keys($data)));

        return back()->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && ! request()->user()->isSuperAdmin()) {
            abort(403, 'Cannot delete super admin.');
        }

        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $request->user()->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $user->tokens()?->delete();

        ActivityLog::record('user.ban', $user, null, $ban->only(['reason', 'expires_at']));

        return back()->with('status', 'User banned.');
    }

    public function unban(User $user): RedirectResponse
    {
        $user->bans()->active()->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('user.unban', $user);

        return back()->with('status', 'User unbanned.');
    }

    public function assignRole(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can change roles.');
        }

        $data = $request->validate([
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ]);

        $before = ['role' => $user->role];
        $user->update(['role' => $data['role']]);

        ActivityLog::record('user.role_assign', $user, $before, ['role' => $data['role']]);

        return back()->with('status', 'Role updated.');
    }
}
