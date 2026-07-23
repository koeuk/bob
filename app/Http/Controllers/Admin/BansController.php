<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Bans\IssueBan;
use App\Actions\Bans\LiftBan;
use App\Actions\Bans\ListBans;
use App\Http\Controllers\Controller;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BansController extends Controller
{
    public function index(Request $request, ListBans $listBans): Response
    {
        ['bans' => $bans, 'counts' => $counts] = $listBans->handle($request);

        return Inertia::render('admin/bans/index', [
            'bans' => $bans,
            'filters' => $request->only(['filter']),
            'counts' => $counts,
            // Dropdown data for the admin ban form — panel-only.
            'bannableUsers' => $this->bannableUsers(),
        ]);
    }

    public function store(Request $request, IssueBan $issueBan): RedirectResponse
    {
        $data = $request->validate(IssueBan::rulesWithUser());

        $user = User::where('uuid', $data['user_uuid'])->firstOrFail();

        $issueBan->handle($user, $data, $request->user(), 'ban.create');

        return back()->with('status', 'Ban created.');
    }

    public function destroy(Request $request, Ban $ban, LiftBan $liftBan): RedirectResponse
    {
        $liftBan->handle($ban, $request->user());

        return back()->with('status', 'Ban lifted.');
    }

    /** Users eligible for banning: not super_admin, not currently banned. */
    private function bannableUsers()
    {
        return User::select('id', 'uuid', 'name', 'email')
            ->with('roles')
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'super_admin'))
            ->whereDoesntHave('bans', fn ($q) => $q->active())
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->each->append('role');
    }
}
