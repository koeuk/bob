<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
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

        return Inertia::render('admin/dashboard', [
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
            'recentReports' => Report::with(['reporter:id,uuid,name', 'reportable'])
                ->latest()
                ->limit(8)
                ->get(),
            'recentActivity' => ActivityLog::with('admin:id,uuid,name')
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }
}
