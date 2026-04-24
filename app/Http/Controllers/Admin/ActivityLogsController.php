<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->with('admin:id,uuid,name');

        if ($action = $request->string('action')->trim()->value()) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($adminUuid = $request->string('admin_uuid')->trim()->value()) {
            $query->whereHas('admin', fn ($q) => $q->where('uuid', $adminUuid));
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to);
        }

        return response()->json(
            $query->latest('created_at')->paginate($request->integer('per_page', 50))
        );
    }
}
