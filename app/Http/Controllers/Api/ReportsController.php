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

/**
 * @group Reports
 */
class ReportsController extends Controller
{
    /**
     * My reports
     *
     * Paginated list of reports filed by the authenticated user.
     *
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [{ "id": 1, "reason": "Spam", "status": "pending", "reportable_type": "App\\Models\\Post" }],
     *   "total": 5
     * }
     */
    public function mine(Request $request): JsonResponse
    {
        $reports = Report::with('reviewer:id,uuid,name')
            ->where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($reports);
    }

    /**
     * File a report
     *
     * Report a post, comment, or user. Returns 409 if you already have a pending report on the same target.
     *
     * @bodyParam type string required One of: `post`, `comment`, `user`. Example: post
     * @bodyParam target_uuid string required UUID of the content being reported. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     * @bodyParam reason string required Description of the issue (max 2,000 characters). Example: This post contains spam.
     *
     * @response 201 { "id": 1, "reason": "This post contains spam.", "status": "pending" }
     * @response 409 { "message": "You already reported this. A moderator will review it." }
     */
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
