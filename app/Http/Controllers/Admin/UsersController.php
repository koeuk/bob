<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\AssignUserRole;
use App\Actions\Bans\IssueBan;
use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\ListUsers;
use App\Actions\Users\ShowUser;
use App\Actions\Users\UnbanUser;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request, ListUsers $listUsers): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => $listUsers->handle($request),
            'filters' => $request->only(['filter', 'sort', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => null,
        ]);
    }

    public function store(Request $request, CreateUser $createUser): RedirectResponse
    {
        $data = $request->validate(CreateUser::rules());

        $user = $createUser->handle($data, $request->user(), $request->file('avatar'));

        return redirect()->route('admin.users.show', $user)->with('status', 'User created.');
    }

    public function show(User $user, ShowUser $showUser): Response
    {
        $profile = $showUser->handle($user);

        return Inertia::render('admin/users/show', [
            'user' => $profile['user'],
            'reportsAgainst' => $profile['reports_against'],
            'activity' => $profile['activity'],
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user->load('roles')->append('role'),
        ]);
    }

    public function update(Request $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate(UpdateUser::rules($user));

        $updateUser->handle(
            $user,
            $data,
            $request->file('avatar'),
            (bool) $request->input('remove_avatar'),
        );

        return back()->with('status', 'User updated.');
    }

    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        $this->authorize('delete', $user);

        $deleteUser->handle($user);

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function ban(Request $request, User $user, IssueBan $issueBan): RedirectResponse
    {
        $this->authorize('ban', $user);

        $data = $request->validate(IssueBan::rules());

        $issueBan->handle($user, $data, $request->user(), 'user.ban');

        return back()->with('status', 'User banned.');
    }

    public function unban(User $user, UnbanUser $unbanUser): RedirectResponse
    {
        $this->authorize('unban', $user);

        $unbanUser->handle($user);

        return back()->with('status', 'User unbanned.');
    }

    public function assignRole(Request $request, User $user, AssignUserRole $assignRole): RedirectResponse
    {
        AssignUserRole::assertAllowed($request->user());

        $data = $request->validate(AssignUserRole::rules());

        $assignRole->handle($user, $data['role'], $request->user());

        return back()->with('status', 'Role updated.');
    }
}
