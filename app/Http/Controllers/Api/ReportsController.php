<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportsController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $reports = Report::with('reviewer:id,uuid,name')
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($reports);
    }

    public function store(Request $request): JsonResponse
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
            return response()->json(['message' => 'You already reported this. A moderator will review it.'], 409);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $model,
            'reportable_id' => $target->id,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return response()->json($report, 201);
    }
}
