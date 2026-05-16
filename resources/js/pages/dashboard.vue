<template>
    <Head title="Dashboard" />
    <AppLayout>
        <!-- Greeting -->
        <section class="pt-2">
            <h1 class="font-sans text-4xl font-semibold tracking-tight sm:text-5xl">
                {{ greeting }}, <span class="text-rust">{{ firstName }}</span>
            </h1>
            <p class="mt-2 max-w-xl text-sm text-muted-foreground">
                Stay on top of your posts, monitor reach, and follow the conversation.
            </p>
        </section>

        <!-- Top grid: hero + figures -->
        <section class="grid gap-4 lg:grid-cols-3">
            <!-- Hero: posts this week -->
            <Link
                href="/posts/mine"
                class="group relative overflow-hidden rounded-3xl bg-rust p-6 text-paper shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-paper/20 backdrop-blur">
                        <Pencil class="size-5" />
                    </span>
                    <ArrowUpRight class="size-5 opacity-70 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
                <div class="mt-8">
                    <div class="text-sm uppercase tracking-wide opacity-80">Posts this week</div>
                    <div class="mt-1 font-sans text-5xl font-semibold tracking-tight">{{ fmt(stats.posts_this_week) }}</div>
                    <div class="mt-1 text-xs opacity-80">
                        <span :class="stats.posts_trend >= 0 ? '' : 'opacity-80'">
                            {{ stats.posts_trend >= 0 ? '+' : '' }}{{ stats.posts_trend }}% vs last week
                        </span>
                    </div>
                </div>
                <div class="absolute -bottom-10 -right-10 size-40 rounded-full bg-paper/10 blur-2xl"></div>
            </Link>

            <!-- Figures grid -->
            <div class="grid grid-cols-2 gap-4 lg:col-span-2">
                <div
                    v-for="f in figures"
                    :key="f.key"
                    class="flex flex-col justify-between rounded-3xl border border-border/60 bg-card p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="inline-flex size-9 items-center justify-center rounded-2xl bg-secondary text-ink">
                            <component :is="f.icon" class="size-[18px]" />
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-medium',
                                f.trend === 'up' ? 'bg-moss/15 text-moss' : 'bg-rust/15 text-rust',
                            ]"
                        >
                            <component :is="f.trend === 'up' ? ArrowUpRight : ArrowDownRight" class="size-3" />
                            {{ f.sub }}
                        </span>
                    </div>
                    <div class="mt-6">
                        <div class="text-xs uppercase tracking-wide text-muted-foreground">{{ f.label }}</div>
                        <div class="mt-1 font-sans text-3xl font-semibold tracking-tight">{{ fmt(f.value) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Chart + weekly goal -->
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Total engagement</h3>
                        <p class="text-xs text-muted-foreground">posts & reactions · past 8 months</p>
                    </div>
                </div>
                <div class="h-72">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <!-- Weekly goal -->
                <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="text-xs uppercase tracking-wide text-muted-foreground">Weekly post goal</div>
                        <span class="text-[11px] text-muted-foreground">resets Sunday</span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="font-sans text-4xl font-semibold tracking-tight">
                            {{ weeklyGoal.progress }}<span class="text-muted-foreground text-2xl">/{{ weeklyGoal.target }}</span>
                        </span>
                        <span class="font-sans text-lg font-semibold text-rust">{{ goalPct }}%</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-secondary">
                        <div class="h-full rounded-full bg-rust transition-all" :style="{ width: goalPct + '%' }"></div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground mb-3">Quick actions</div>
                    <div class="flex flex-col gap-2">
                        <Link
                            href="/feed"
                            class="inline-flex items-center gap-2 rounded-2xl bg-ink px-4 py-2.5 text-sm font-medium text-paper hover:opacity-90 transition-opacity"
                        >
                            <Pencil class="size-4" /> Write a post
                        </Link>
                        <Link
                            href="/posts/mine"
                            class="inline-flex items-center gap-2 rounded-2xl border border-border px-4 py-2.5 text-sm font-medium hover:bg-secondary transition-colors"
                        >
                            <Send class="size-4" /> My posts
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent posts + recent activity -->
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">My recent posts</h3>
                        <p class="text-xs text-muted-foreground">latest published</p>
                    </div>
                    <Link href="/posts/mine" class="text-xs font-medium text-rust hover:underline">View all &rarr;</Link>
                </div>
                <ul v-if="myPosts.length" class="divide-y divide-border/60">
                    <li v-for="p in myPosts" :key="p.uuid" class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-rust/10 text-rust">
                            <Newspaper class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <Link :href="`/posts/${p.uuid}`" class="truncate font-medium hover:text-rust transition-colors">
                                    {{ truncate(p.body) }}
                                </Link>
                                <span
                                    :class="[
                                        'inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium',
                                        p.status === 'flagged' ? 'bg-rust/15 text-rust'
                                        : p.status === 'hidden' ? 'bg-ink/10 text-ink'
                                        : 'bg-moss/15 text-moss',
                                    ]"
                                >{{ p.status }}</span>
                            </div>
                            <div class="mt-0.5 flex items-center gap-3 text-[11px] text-muted-foreground">
                                <span class="inline-flex items-center gap-1"><Heart class="size-3" /> {{ p.likes_count }}</span>
                                <span class="inline-flex items-center gap-1"><MessageSquare class="size-3" /> {{ p.comments_count }}</span>
                            </div>
                        </div>
                        <span class="shrink-0 text-[11px] text-muted-foreground">{{ timeAgo(p.created_at) }}</span>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                    You haven't posted yet.
                    <Link href="/feed" class="font-medium text-rust hover:underline">Write your first →</Link>
                </div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Recent activity</h3>
                        <p class="text-xs text-muted-foreground">comments on your posts</p>
                    </div>
                </div>
                <ul v-if="recentActivity.length" class="divide-y divide-border/60">
                    <li v-for="a in recentActivity" :key="a.uuid" class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-secondary text-ink">
                            <MessageSquare class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium truncate">{{ truncate(a.body, 50) }}</div>
                            <div class="text-xs text-muted-foreground">{{ a.user?.name ?? 'unknown' }}</div>
                        </div>
                        <span class="shrink-0 text-[11px] text-muted-foreground">{{ timeAgo(a.created_at) }}</span>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                    No activity on your posts yet.
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import {
    ArrowDownRight,
    ArrowUpRight,
    Heart,
    MessageSquare,
    Newspaper,
    Pencil,
    Send,
    ThumbsUp,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    stats: { type: Object, required: true },
    weeklyGoal: { type: Object, required: true },
    engagementSeries: { type: Array, default: () => [] },
    recentActivity: { type: Array, default: () => [] },
    myPosts: { type: Array, default: () => [] },
});

const page = usePage();
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'friend');

const hour = new Date().getHours();
const greeting = computed(() => {
    if (hour < 5) return 'Still up';
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    if (hour < 21) return 'Good evening';
    return 'Late shift';
});

const fmt = (n) => new Intl.NumberFormat('en-US').format(n ?? 0);

const goalPct = computed(() => {
    const t = props.weeklyGoal.target || 1;
    return Math.min(100, Math.round((props.weeklyGoal.progress / t) * 100));
});

const figures = computed(() => [
    {
        key: 'posts_total',
        label: 'Your Posts',
        value: props.stats.posts_total,
        sub: `${props.stats.posts_trend >= 0 ? '+' : ''}${props.stats.posts_trend}% vs last week`,
        icon: Newspaper,
        trend: props.stats.posts_trend >= 0 ? 'up' : 'down',
    },
    {
        key: 'reactions_total',
        label: 'Reactions',
        value: props.stats.reactions_total,
        sub: `+${fmt(props.stats.reactions_this_month)} this month`,
        icon: ThumbsUp,
        trend: 'up',
    },
    {
        key: 'comments_received',
        label: 'Comments',
        value: props.stats.comments_received,
        sub: `+${fmt(props.stats.comments_this_month)} this month`,
        icon: MessageSquare,
        trend: 'up',
    },
    {
        key: 'posts_this_week',
        label: 'This Week',
        value: props.stats.posts_this_week,
        sub: 'posts published',
        icon: Heart,
        trend: props.stats.posts_this_week > 0 ? 'up' : 'down',
    },
]);

const chartData = computed(() => ({
    labels: props.engagementSeries.map((d) => d.label),
    datasets: [
        {
            label: 'Posts',
            data: props.engagementSeries.map((d) => d.posts),
            backgroundColor: 'oklch(0.22 0.012 60)',
            borderRadius: 6,
            barPercentage: 0.7,
            categoryPercentage: 0.7,
        },
        {
            label: 'Reactions',
            data: props.engagementSeries.map((d) => d.reactions),
            backgroundColor: 'oklch(0.57 0.17 35)',
            borderRadius: 6,
            barPercentage: 0.7,
            categoryPercentage: 0.7,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: {
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 16,
                color: 'oklch(0.5 0.018 65)',
                font: { size: 11 },
            },
        },
        tooltip: { intersect: false, mode: 'index' },
    },
    scales: {
        x: {
            grid: { display: false, drawBorder: false },
            ticks: { color: 'oklch(0.5 0.018 65)', font: { size: 10 } },
        },
        y: {
            grid: { color: 'oklch(0.82 0.02 75 / 0.4)', drawBorder: false },
            ticks: { color: 'oklch(0.5 0.018 65)', font: { size: 10 } },
        },
    },
};

const truncate = (t, n = 60) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : (t ?? '');

const timeAgo = (iso) => {
    if (!iso) return '';
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};
</script>
