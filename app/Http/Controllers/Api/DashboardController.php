<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Dashboard
 */
class DashboardController extends Controller
{
    /**
     * User dashboard stats
     *
     * Returns post/comment/reaction statistics, weekly goal progress, an 8-month engagement series,
     * recent activity on the user's posts, and their 3 latest posts.
     *
     * @response 200 {
     *   "stats": {
     *     "posts_total": 14, "posts_this_week": 2, "posts_last_week": 1, "posts_trend": 100,
     *     "comments_received": 28, "comments_this_month": 10,
     *     "reactions_total": 45, "reactions_this_month": 8
     *   },
     *   "weekly_goal": { "target": 20, "progress": 2 },
     *   "engagement_series": [{ "label": "Oct", "posts": 3, "reactions": 12 }],
     *   "recent_activity": [],
     *   "my_posts": []
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $postIds = Post::where('user_id', $user->id)->pluck('id');
        $commentIds = Comment::where('user_id', $user->id)->pluck('id');

        $weekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();
        $monthStart = now()->startOfMonth();

        $postsThisWeek = Post::where('user_id', $user->id)->where('created_at', '>=', $weekStart)->count();
        $postsLastWeek = Post::where('user_id', $user->id)->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $postLikes = Like::where('likeable_type', Post::class)->whereIn('likeable_id', $postIds)->count();
        $commentLikes = Like::where('likeable_type', Comment::class)->whereIn('likeable_id', $commentIds)->count();

        $reactionsThisMonth = Like::where('likeable_type', Post::class)
            ->whereIn('likeable_id', $postIds)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $engagementSeries = $this->monthlySeries($postIds, $user->id);

        $recentActivity = Comment::with(['user:id,uuid,name,avatar', 'post:id,uuid,body'])
            ->whereIn('post_id', $postIds)
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $myPosts = Post::where('user_id', $user->id)
            ->withCount(['comments', 'likes'])
            ->latest()
            ->limit(3)
            ->get();

        return response()->json([
            'stats' => [
                'posts_total' => Post::where('user_id', $user->id)->count(),
                'posts_this_week' => $postsThisWeek,
                'posts_last_week' => $postsLastWeek,
                'posts_trend' => $postsLastWeek === 0
                    ? ($postsThisWeek > 0 ? 100 : 0)
                    : round((($postsThisWeek - $postsLastWeek) / max($postsLastWeek, 1)) * 100),
                'comments_received' => Comment::whereIn('post_id', $postIds)->count(),
                'comments_this_month' => Comment::whereIn('post_id', $postIds)->where('created_at', '>=', $monthStart)->count(),
                'reactions_total' => $postLikes + $commentLikes,
                'reactions_this_month' => $reactionsThisMonth,
            ],
            'weekly_goal' => [
                'target' => 20,
                'progress' => $postsThisWeek,
            ],
            'engagement_series' => $engagementSeries,
            'recent_activity' => $recentActivity,
            'my_posts' => $myPosts,
        ]);
    }

    private function monthlySeries($postIds, int $userId): array
    {
        $since = now()->subMonths(7)->startOfMonth();

        $postsByMonth = Post::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket"), DB::raw('COUNT(*) as count'))
            ->groupBy('bucket')
            ->pluck('count', 'bucket');

        $reactionsByMonth = Like::where('likeable_type', Post::class)
            ->whereIn('likeable_id', $postIds)
            ->where('created_at', '>=', $since)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket"), DB::raw('COUNT(*) as count'))
            ->groupBy('bucket')
            ->pluck('count', 'bucket');

        $out = [];
        for ($i = 7; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $key = $m->format('Y-m');
            $out[] = [
                'label' => $m->format('M'),
                'posts' => (int) ($postsByMonth[$key] ?? 0),
                'reactions' => (int) ($reactionsByMonth[$key] ?? 0),
            ];
        }

        return $out;
    }
}
