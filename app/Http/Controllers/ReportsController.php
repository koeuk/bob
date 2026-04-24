<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function mine(Request $request): Response
    {
        $reports = Report::with('reviewer:id,uuid,name')
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('reports/mine', [
            'reports' => $reports,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['post', 'comment', 'user'])],
            'target_uuid' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $model = match ($data['type']) {
            'post' => Post::class,
            'comment' => Comment::class,
            'user' => User::class,
        };

        $target = $model::where('uuid', $data['target_uuid'])->firstOrFail();

        $existing = Report::where('reporter_id', $request->user()->id)
            ->where('reportable_type', $model)
            ->where('reportable_id', $target->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('status', 'You already reported this. A moderator will review it.');
        }

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $model,
            'reportable_id' => $target->id,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Thanks — report filed. Moderators will review it.');
    }
}
