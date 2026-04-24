<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = QueryBuilder::for(ActivityLog::class)
            ->with('admin:id,uuid,name')
            ->allowedFilters([
                AllowedFilter::partial('action'),
                AllowedFilter::callback('admin_uuid', function ($q, $value) {
                    $q->whereHas('admin', fn ($a) => $a->where('uuid', $value));
                }),
                AllowedFilter::callback('from', fn ($q, $v) => $q->where('created_at', '>=', $v)),
                AllowedFilter::callback('to', fn ($q, $v) => $q->where('created_at', '<=', $v)),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 50));

        return response()->json($logs);
    }
}
