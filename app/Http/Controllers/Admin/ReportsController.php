<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reports\ListReports;
use App\Actions\Reports\TransitionReport;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request, ListReports $listReports): Response
    {
        ['reports' => $reports, 'counts' => $counts] = $listReports->handle($request);

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
            'filters' => $request->only(['filter', 'sort']),
            'counts' => $counts,
        ]);
    }

    public function show(Report $report): Response
    {
        $report->load(['reporter:id,uuid,name,email', 'reviewer:id,uuid,name', 'reportable']);

        return Inertia::render('admin/reports/show', [
            'report' => $report,
        ]);
    }

    public function review(Request $request, Report $report, TransitionReport $transition): RedirectResponse
    {
        $transition->handle($report, TransitionReport::REVIEWED, $request->user());

        return back()->with('status', 'Report marked as reviewed.');
    }

    public function resolve(Request $request, Report $report, TransitionReport $transition): RedirectResponse
    {
        $data = $request->validate(TransitionReport::rules(TransitionReport::RESOLVED));

        $transition->handle($report, TransitionReport::RESOLVED, $request->user(), $data['resolution_note']);

        return back()->with('status', 'Report resolved.');
    }

    public function dismiss(Request $request, Report $report, TransitionReport $transition): RedirectResponse
    {
        $data = $request->validate(TransitionReport::rules(TransitionReport::DISMISSED));

        $transition->handle($report, TransitionReport::DISMISSED, $request->user(), $data['resolution_note'] ?? null);

        return back()->with('status', 'Report dismissed.');
    }
}
