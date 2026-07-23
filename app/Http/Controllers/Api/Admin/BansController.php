<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Bans\IssueBan;
use App\Actions\Bans\LiftBan;
use App\Actions\Bans\ListBans;
use App\Http\Controllers\Controller;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin: Bans
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class BansController extends Controller
{
    /**
     * List bans
     *
     * @queryParam filter[search] string Search by banned user's name or email. Example: jane
     * @queryParam filter[active] boolean Show only active bans. Example: 1
     * @queryParam sort string One of: `created_at`, `-created_at`, `expires_at`. Example: -created_at
     * @queryParam per_page int Results per page (default 25). Example: 25
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": { "data": [], "total": 7 },
     *   "counts": { "all": 8, "active": 7 }
     * }
     */
    public function index(Request $request, ListBans $listBans): JsonResponse
    {
        ['bans' => $bans, 'counts' => $counts] = $listBans->handle($request);

        return response()->json([
            'data' => $bans,
            'counts' => $counts,
        ]);
    }

    /**
     * Create ban
     *
     * Immediately revokes all of the target user's tokens.
     *
     * @bodyParam user_uuid string required UUID of the user to ban. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam reason string required Reason for the ban (max 2,000 chars). Example: Repeated violations.
     * @bodyParam expires_at string ISO 8601 datetime for a temporary ban. Omit for permanent. Example: 2026-12-31T23:59:59
     *
     * @response 201 { "id": 10, "user_id": 23, "reason": "Repeated violations.", "expires_at": null }
     * @response 403 { "message": "This action is unauthorized." }
     */
    public function store(Request $request, IssueBan $issueBan): JsonResponse
    {
        $data = $request->validate(IssueBan::rulesWithUser());

        $user = User::where('uuid', $data['user_uuid'])->firstOrFail();

        $ban = $issueBan->handle($user, $data, $request->user(), 'ban.create');

        return response()->json($ban->load(['user:id,uuid,name,email', 'bannedBy:id,uuid,name']), 201);
    }

    /**
     * Lift ban
     *
     * Expires the ban immediately by setting `expires_at` to the past.
     *
     * @urlParam ban string required Ban UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "message": "Ban lifted." }
     */
    public function destroy(Request $request, Ban $ban, LiftBan $liftBan): JsonResponse
    {
        $liftBan->handle($ban, $request->user());

        return response()->json(['message' => 'Ban lifted.']);
    }
}
