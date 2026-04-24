<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();

        return response()->json([
            'users_total' => User::count(),
            'users_today' => User::where('created_at', '>=', $today)->count(),
            'users_active_now' => User::where('updated_at', '>=', now()->subMinutes(5))->count(),
            'posts_total' => Post::count(),
            'posts_today' => Post::where('created_at', '>=', $today)->count(),
            'comments_total' => Comment::count(),
            'reports_pending' => Report::where('status', 'pending')->count(),
            'bans_active' => Ban::active()->count(),
        ]);
    }

    public function charts(): JsonResponse
    {
        $users = User::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $posts = Post::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $reports = Report::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json([
            'users_30d' => $users,
            'posts_7d' => $posts,
            'reports_30d' => $reports,
        ]);
    }

    public function recentActivity(): JsonResponse
    {
        $activity = ActivityLog::with('admin:id,uuid,name')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $reports = Report::with('reporter:id,uuid,name')
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'activity' => $activity,
            'recent_reports' => $reports,
        ]);
    }
}
