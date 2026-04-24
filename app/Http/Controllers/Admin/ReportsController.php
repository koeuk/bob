<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::query()
            ->with(['reporter:id,uuid,name', 'reviewer:id,uuid,name', 'reportable']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('type')->trim()->value()) {
            $query->where('reportable_type', $type);
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 25))
        );
    }

    public function show(Report $report): JsonResponse
    {
        $report->load(['reporter:id,uuid,name,email', 'reviewer:id,uuid,name', 'reportable']);

        return response()->json(['report' => $report]);
    }

    public function review(Request $request, Report $report): JsonResponse
    {
        $report->update([
            'status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('report.review', $report);

        return response()->json(['report' => $report]);
    }

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

        return response()->json(['report' => $report]);
    }

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

        return response()->json(['report' => $report]);
    }
}
