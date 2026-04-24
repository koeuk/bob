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
    Filter,
    Heart,
    ImagePlus,
    MessageCircle,
    Pencil,
    Search,
    Send,
    UserPlus,
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
const user = computed(() => page.props.auth?.user);
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'friend');

const hour = new Date().getHours();
const greeting = computed(() => {
    if (hour < 5) return 'Night owl';
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
                boxWidth: 8,
                boxHeight: 8,
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
            display: false,
            grid: { display: false, drawBorder: false },
        },
    },
};

const truncate = (t, n = 60) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;

const timeAgo = (iso) => {
    if (!iso) return '';
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

const refLabel = (idx) => `ACT-${String(idx + 1).padStart(4, '0')}`;
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <!-- Greeting -->
        <section class="pt-2">
            <h1 class="font-sans text-4xl font-semibold tracking-tight sm:text-5xl">
                {{ greeting }}, <span>{{ firstName }}</span>
            </h1>
            <p class="mt-2 max-w-xl text-sm text-muted-foreground">
                Stay on top of your posts, monitor reach, and follow the conversation.
            </p>
        </section>

        <!-- Top grid -->
        <section class="grid gap-4 lg:grid-cols-4">
            <!-- Your posts -->
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <span class="text-sm font-medium">Your posts</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-moss/15 px-2 py-0.5 text-[10px] font-medium text-moss">
                        <span class="size-1.5 rounded-full bg-moss"></span>
                        total
                    </span>
                </div>
                <div class="mt-4 font-sans text-4xl font-semibold tracking-tight">{{ fmt(stats.posts_total) }}</div>
                <div class="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                    <component :is="stats.posts_trend >= 0 ? ArrowUpRight : ArrowDownRight" class="size-3" />
                    <span :class="stats.posts_trend >= 0 ? 'text-moss' : 'text-rust'">
                        {{ stats.posts_trend >= 0 ? '+' : '' }}{{ stats.posts_trend }}%
                    </span>
                    <span>than last week</span>
                </div>

                <div class="mt-5 flex gap-2">
                    <Link
                        href="/feed"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-ink px-4 py-2.5 text-sm font-medium text-paper hover:opacity-90"
                    >
                        <Pencil class="size-4" /> New post
                    </Link>
                    <Link
                        href="/posts/mine"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-border px-4 py-2.5 text-sm font-medium hover:bg-secondary"
                    >
                        <Send class="size-4" /> My posts
                    </Link>
                </div>
            </div>

            <!-- Posts this week (rust hero) -->
            <div class="relative overflow-hidden rounded-3xl bg-rust p-5 text-paper shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Posts this week</span>
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-paper/20">
                        <Pencil class="size-4" />
                    </span>
                </div>
                <div class="mt-8 font-sans text-5xl font-semibold tracking-tight">{{ fmt(stats.posts_this_week) }}</div>
                <div class="mt-1 text-xs opacity-80">
                    <span v-if="stats.posts_trend >= 0">+{{ stats.posts_trend }}%</span>
                    <span v-else>{{ stats.posts_trend }}%</span>
                    vs last week
                </div>
                <div class="absolute -bottom-10 -right-10 size-40 rounded-full bg-paper/10 blur-2xl"></div>
            </div>

            <!-- Reactions -->
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Reactions</span>
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-rust/10 text-rust">
                        <Heart class="size-4" />
                    </span>
                </div>
                <div class="mt-8 font-sans text-5xl font-semibold tracking-tight">{{ fmt(stats.reactions_total) }}</div>
                <div class="mt-1 text-xs text-muted-foreground">+{{ fmt(stats.reactions_this_month) }} this month</div>
            </div>

            <!-- Chart -->
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="mb-2 flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-semibold">Total engagement</h3>
                        <p class="text-[11px] text-muted-foreground">posts & reactions · past 8 months</p>
                    </div>
                </div>
                <div class="h-36">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>
        </section>

        <!-- Second row -->
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Comments</span>
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-secondary text-ink">
                        <MessageCircle class="size-4" />
                    </span>
                </div>
                <div class="mt-6 font-sans text-4xl font-semibold tracking-tight">{{ fmt(stats.comments_received) }}</div>
                <div class="mt-1 text-xs text-muted-foreground">+{{ fmt(stats.comments_this_month) }} this month</div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Reactions this month</span>
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-moss/10 text-moss">
                        <UserPlus class="size-4" />
                    </span>
                </div>
                <div class="mt-6 font-sans text-4xl font-semibold tracking-tight">{{ fmt(stats.reactions_this_month) }}</div>
                <div class="mt-1 text-xs text-muted-foreground">live engagement on your posts</div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm font-medium">My recent posts</span>
                    <Link href="/posts/mine" class="text-[11px] text-rust hover:underline">View all &rarr;</Link>
                </div>
                <div v-if="myPosts.length" class="space-y-3">
                    <Link
                        v-for="p in myPosts"
                        :key="p.uuid"
                        :href="`/posts/${p.uuid}`"
                        class="block rounded-2xl bg-secondary/40 p-3 transition-colors hover:bg-secondary"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1 text-sm">{{ truncate(p.body) }}</div>
                            <span
                                :class="[
                                    'inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium',
                                    p.status === 'flagged' ? 'bg-rust/15 text-rust'
                                    : p.status === 'hidden' ? 'bg-ink/10 text-ink'
                                    : 'bg-moss/15 text-moss',
                                ]"
                            >{{ p.status }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-3 text-[11px] text-muted-foreground">
                            <span class="inline-flex items-center gap-1"><Heart class="size-3" /> {{ p.likes_count }}</span>
                            <span class="inline-flex items-center gap-1"><MessageCircle class="size-3" /> {{ p.comments_count }}</span>
                        </div>
                    </Link>
                </div>
                <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-xs text-muted-foreground">
                    You haven't posted yet.
                    <Link href="/feed" class="font-medium text-rust hover:underline">Write your first →</Link>
                </div>
            </div>
        </section>

        <!-- Third row: weekly goal + recent activity -->
        <section class="grid gap-4 lg:grid-cols-[1fr_1.8fr]">
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold">Weekly post goal</h3>
                    <span class="text-[11px] text-muted-foreground">resets Sunday</span>
                </div>
                <div class="mt-3 flex items-end justify-between">
                    <div class="font-sans text-5xl font-semibold leading-none tracking-tight">
                        {{ weeklyGoal.progress }}<span class="text-muted-foreground">/{{ weeklyGoal.target }}</span>
                    </div>
                    <div class="font-sans text-2xl font-semibold text-rust">{{ goalPct }}%</div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-secondary">
                    <div class="h-full rounded-full bg-rust transition-all" :style="{ width: goalPct + '%' }"></div>
                </div>

                <div class="mt-6 flex gap-2">
                    <Link
                        href="/feed"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-ink px-4 py-2.5 text-sm font-medium text-paper hover:opacity-90"
                    >
                        <Pencil class="size-4" /> Write a post
                    </Link>
                </div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="text-base font-semibold">Recent activity</h3>
                    <div class="flex items-center gap-2">
                        <div class="relative hidden sm:block">
                            <Search class="pointer-events-none absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="search"
                                placeholder="Search"
                                class="h-9 w-48 rounded-full bg-secondary/60 pl-9 pr-3 text-xs outline-none focus:bg-secondary"
                            />
                        </div>
                        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-border px-3 text-xs font-medium hover:bg-secondary">
                            <Filter class="size-3.5" /> Filter
                        </button>
                    </div>
                </div>

                <div v-if="recentActivity.length" class="-mx-6">
                    <div class="grid grid-cols-[2.5rem_6rem_1fr_1fr_6rem_5rem] items-center gap-3 px-6 pb-2 text-[10px] uppercase tracking-wide text-muted-foreground">
                        <span></span>
                        <span>Ref</span>
                        <span>Activity</span>
                        <span>Author</span>
                        <span>Status</span>
                        <span>When</span>
                    </div>
                    <ul class="divide-y divide-border/60">
                        <li
                            v-for="(a, i) in recentActivity"
                            :key="a.uuid"
                            class="grid grid-cols-[2.5rem_6rem_1fr_1fr_6rem_5rem] items-center gap-3 px-6 py-3 text-sm"
                        >
                            <input type="checkbox" class="size-4 rounded accent-rust" />
                            <span class="font-mono text-[11px] text-muted-foreground">{{ refLabel(i) }}</span>
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-rust/10 text-rust">
                                    <MessageCircle class="size-3.5" />
                                </span>
                                <span class="truncate">{{ truncate(a.body, 50) }}</span>
                            </div>
                            <span class="truncate text-muted-foreground">{{ a.user?.name ?? 'unknown' }}</span>
                            <span class="inline-flex items-center gap-1 text-[11px]">
                                <span class="size-1.5 rounded-full bg-moss"></span>
                                <span class="text-moss">Published</span>
                            </span>
                            <span class="text-[11px] text-muted-foreground">{{ timeAgo(a.created_at) }}</span>
                        </li>
                    </ul>
                </div>

                <div v-else class="rounded-2xl bg-secondary/40 py-12 text-center text-sm text-muted-foreground">
                    No activity on your posts yet.
                </div>
            </div>
        </section>
    </AppLayout>
</template>
