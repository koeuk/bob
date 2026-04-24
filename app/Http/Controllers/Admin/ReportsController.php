<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReportsController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
            'filters' => $request->only(['filter', 'sort']),
            'counts' => [
                'pending' => Report::where('status', 'pending')->count(),
                'reviewed' => Report::where('status', 'reviewed')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
                'dismissed' => Report::where('status', 'dismissed')->count(),
            ],
        ]);
    }

    public function show(Report $report): Response
    {
        $report->load(['reporter:id,uuid,name,email', 'reviewer:id,uuid,name', 'reportable']);

        return Inertia::render('admin/reports/show', [
            'report' => $report,
        ]);
    }

    public function review(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        ActivityLog::record('report.review', $report);

        return back()->with('status', 'Report marked as reviewed.');
    }

    public function resolve(Request $request, Report $report): RedirectResponse
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

        return back()->with('status', 'Report resolved.');
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
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

        return back()->with('status', 'Report dismissed.');
    }
}
