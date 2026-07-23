<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Reports\ListReports;
use App\Actions\Reports\TransitionReport;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request, ListReports $listReports): JsonResponse
    {
        ['reports' => $reports, 'counts' => $counts] = $listReports->handle($request);

        return response()->json([
            'data' => $reports,
            'counts' => $counts,
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
    public function review(Request $request, Report $report, TransitionReport $transition): JsonResponse
    {
        return response()->json(
            $transition->handle($report, TransitionReport::REVIEWED, $request->user())
        );
    }

    /**
     * Resolve report
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam resolution_note string required Explanation of how it was resolved (max 2,000 chars). Example: Content removed, user warned.
     *
     * @response 200 { "id": 1, "status": "resolved", "resolution_note": "Content removed, user warned." }
     */
    public function resolve(Request $request, Report $report, TransitionReport $transition): JsonResponse
    {
        $data = $request->validate(TransitionReport::rules(TransitionReport::RESOLVED));

        return response()->json(
            $transition->handle($report, TransitionReport::RESOLVED, $request->user(), $data['resolution_note'])
        );
    }

    /**
     * Dismiss report
     *
     * @urlParam report string required Report UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam resolution_note string Optional explanation (max 2,000 chars). Example: No violation found.
     *
     * @response 200 { "id": 1, "status": "dismissed" }
     */
    public function dismiss(Request $request, Report $report, TransitionReport $transition): JsonResponse
    {
        $data = $request->validate(TransitionReport::rules(TransitionReport::DISMISSED));

        return response()->json(
            $transition->handle($report, TransitionReport::DISMISSED, $request->user(), $data['resolution_note'] ?? null)
        );
    }
}
