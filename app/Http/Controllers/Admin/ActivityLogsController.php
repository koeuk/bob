<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivityLogs\ListActivityLogs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogsController extends Controller
{
    public function index(Request $request, ListActivityLogs $listLogs): Response
    {
        return Inertia::render('admin/activity-logs/index', [
            'logs' => $listLogs->handle($request),
            'filters' => $request->only(['filter']),
        ]);
    }
}
