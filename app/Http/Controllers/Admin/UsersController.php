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
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can create admin-level users.');
        }

        $attrs = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if ($request->hasFile('avatar')) {
            $attrs['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($attrs);
        $user->assignRole($data['role']);

        ActivityLog::record('user.create', $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $data['role']]);

        return redirect()->route('admin.users.show', $user)->with('status', 'User created.');
    }

    public function show(User $user): Response
    {
        $user->load([
            'roles',
            'bans' => fn ($q) => $q->with('bannedBy:id,uuid,name')->latest(),
        ])->loadCount(['posts', 'comments', 'reportsFiled']);
        $user->append('role');

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

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user->load('roles')->append('role'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->outranks($user), 403, 'You cannot manage a user with equal or higher privileges.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $before = $user->only(['name', 'email']);

        $attrs = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => !empty($data['password']) ? $data['password'] : null,
        ], fn ($v) => $v !== null);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $attrs['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->input('remove_avatar') && $user->avatar) {
            \Storage::disk('public')->delete($user->avatar);
            $attrs['avatar'] = null;
        }

        $user->update($attrs);

        ActivityLog::record('user.update', $user, $before, $user->only(['name', 'email']));

        return back()->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(request()->user()->outranks($user), 403, 'You cannot manage a user with equal or higher privileges.');

        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->outranks($user), 403, 'You cannot ban a user with equal or higher privileges.');

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
        $user->syncRoles([$data['role']]);

        ActivityLog::record('user.role_assign', $user, $before, ['role' => $data['role']]);

        return back()->with('status', 'Role updated.');
    }
}
