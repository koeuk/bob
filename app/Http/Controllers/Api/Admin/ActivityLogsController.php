<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\ActivityLogs\ListActivityLogs;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin: Activity Logs
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class ActivityLogsController extends Controller
{
    /**
     * List activity logs
     *
     * @queryParam filter[action] string Partial match on action name. Example: user.ban
     * @queryParam filter[admin_uuid] string Filter by admin UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @queryParam filter[from] string ISO 8601 start date (inclusive). Example: 2026-05-01
     * @queryParam filter[to] string ISO 8601 end date (inclusive). Example: 2026-05-31
     * @queryParam sort string One of: `created_at`, `-created_at`. Example: -created_at
     * @queryParam per_page int Results per page (default 50). Example: 50
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{ "id": 1, "action": "user.ban", "target_type": "App\\Models\\User", "before": null, "after": {}, "admin": {} }],
     *   "total": 120
     * }
     */
    public function index(Request $request, ListActivityLogs $listLogs): JsonResponse
    {
        return response()->json($listLogs->handle($request));
    }
}
