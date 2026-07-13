<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Reports
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class ReportsController extends Controller
{
    /**
     * List reports
     *
     * @queryParam filter[status] string One of: `pending`, `reviewed`, `resolved`, `dismissed`. Example: pending
     * @queryParam filter[type] string Reportable type. One of: `App\Models\Post`, `App\Models\Comment`, `App\Models\User`. Example: App\Models\Post
     * @queryParam sort string One of: `created_at`, `-created_at`, `status`. Example: -created_at
     * @queryParam per_page int Results per page (default 25). Example: 25
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "data": { "data": [], "total": 11 },
     *   "counts": { "pending": 11, "reviewed": 0, "resolved": 0, "dismissed": 0 }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $reports = QueryBuilder::for(Report::class)
            ->with(['reporter:id,uuid,name', 'reviewer:id,uuid,name', 'reportable'])
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type', 'reportable_type'),
            ])
            ->allowedSorts(...['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json([
            'data' => $reports,
            'counts' => [
                'pending' => Report::where('status', 'pending')->count(),
                'reviewed' => Report::where('status', 'reviewed')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
                'dismissed' => Report::where('status', 'dismissed')->count(),
            ],
        ]);
    }

    /**
     * Get report
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "id": 1, "reason": "Hate speech", "status": "pending", "reporter": {}, "reportable": {} }
     */
    public function show(Report $report): JsonResponse
    {
        $report->load(['reporter:id,uuid,name,email', 'reviewer:id,uuid,name', 'reportable']);

        return response()->json($report);
    }

    /**
     * Mark as reviewed
     *
     * Sets status to `reviewed` and records the reviewing moderator.
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     *
     * @response 200 { "id": 1, "status": "reviewed", "reviewed_by": 2, "reviewed_at": "2026-05-11T00:00:00Z" }
     */
    public function review(Request $request, Report $report): JsonResponse
    {
        $report->update([
            'status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('report.review', $report);

        return response()->json($report->fresh());
    }

    /**
     * Resolve report
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam resolution_note string required Explanation of how it was resolved (max 2,000 chars). Example: Content removed, user warned.
     *
     * @response 200 { "id": 1, "status": "resolved", "resolution_note": "Content removed, user warned." }
     */
    public function resolve(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'resolution_note' => ['required', 'string', 'max:2000'],
        ]);

        $report->update([
            'status' => 'resolved',
            'resolution_note' => $data['resolution_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('report.resolve', $report, null, ['note' => $data['resolution_note']]);

        return response()->json($report->fresh());
    }

    /**
     * Dismiss report
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam resolution_note string Optional explanation (max 2,000 chars). Example: No violation found.
     *
     * @response 200 { "id": 1, "status": "dismissed" }
     */
    public function dismiss(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update([
            'status' => 'dismissed',
            'resolution_note' => $data['resolution_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('report.dismiss', $report);

        return response()->json($report->fresh());
    }
}
