<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin: Dashboard
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 */
class DashboardController extends Controller
{
    /**
     * Admin dashboard stats
     *
     * System-wide stats for the last 30 days, daily signup/post series, recent reports and activity logs.
     *
     * @response 200 {
     *   "stats": {
     *     "users_total": 44, "users_today": 0, "users_active_5m": 1,
     *     "posts_total": 83, "posts_today": 1, "comments_total": 210,
     *     "reports_pending": 11, "bans_active": 7
     *   },
     *   "series": { "signups": [], "posts": [] },
     *   "recent_reports": [],
     *   "recent_activity": []
     * }
     */
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();
        $since = now()->subDays(29)->startOfDay();

        $signups = User::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $postsPerDay = Post::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'stats' => [
                'users_total' => User::count(),
                'users_today' => User::where('created_at', '>=', $today)->count(),
                'users_active_5m' => User::where('updated_at', '>=', now()->subMinutes(5))->count(),
                'posts_total' => Post::count(),
                'posts_today' => Post::where('created_at', '>=', $today)->count(),
                'comments_total' => Comment::count(),
                'reports_pending' => Report::where('status', 'pending')->count(),
                'bans_active' => Ban::active()->count(),
            ],
            'series' => [
                'signups' => $signups,
                'posts' => $postsPerDay,
            ],
            'recent_reports' => Report::with(['reporter:id,uuid,name', 'reportable'])
                ->latest()
                ->limit(8)
                ->get(),
            'recent_activity' => ActivityLog::with('admin:id,uuid,name')
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }
}
