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
                class="group relative overflow-hidden rounded-3xl bg-rust p-6 text-paper shadow-sm transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
            >
                <div class="flex items-center justify-between">
                    <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-paper/20 backdrop-blur-sm">
                        <Pencil class="size-5" />
                    </span>
                    <ArrowUpRight class="size-5 opacity-60 transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
                <div class="mt-8">
                    <div class="text-xs uppercase tracking-widest opacity-70 font-medium">Posts this week</div>
                    <div class="mt-1.5 font-sans text-6xl font-semibold tracking-tight leading-none">{{ fmt(stats.posts_this_week) }}</div>
                    <div class="mt-2 text-xs opacity-70">
                        {{ stats.posts_trend >= 0 ? '+' : '' }}{{ stats.posts_trend }}% vs last week
                    </div>
                </div>
                <div class="pointer-events-none absolute -bottom-12 -right-12 size-48 rounded-full bg-paper/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -top-8 -left-8 size-32 rounded-full bg-paper/5 blur-2xl"></div>
            </Link>

            <!-- Figures grid -->
            <div class="grid grid-cols-2 gap-4 lg:col-span-2">
                <Card
                    v-for="f in figures"
                    :key="f.key"
                    class="rounded-3xl border-border/60 gap-0 p-5 flex flex-col justify-between transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-default"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-flex size-9 items-center justify-center rounded-2xl bg-secondary text-ink shrink-0">
                            <component :is="f.icon" class="size-[18px]" />
                        </span>
                        <Badge
                            :class="[
                                'rounded-full border-0 inline-flex items-center gap-0.5 px-2 py-0.5 text-[10px] font-medium shrink-0',
                                f.trend === 'up' ? 'bg-moss/15 text-moss' : 'bg-rust/15 text-rust',
                            ]"
                        >
                            <component :is="f.trend === 'up' ? ArrowUpRight : ArrowDownRight" class="size-3" />
                            {{ f.sub }}
                        </Badge>
                    </div>
                    <div class="mt-5">
                        <div class="text-[11px] uppercase tracking-widest text-muted-foreground font-medium">{{ f.label }}</div>
                        <div class="mt-1 font-sans text-3xl font-semibold tracking-tight">{{ fmt(f.value) }}</div>
                    </div>
                </Card>
            </div>
        </section>

        <!-- Chart + weekly goal + quick actions -->
        <section class="grid gap-4 lg:grid-cols-3">
            <Card class="rounded-3xl border-border/60 gap-0 lg:col-span-2 transition-all duration-200 hover:shadow-md">
                <CardHeader class="px-6 pt-6 pb-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="text-base">Total engagement</CardTitle>
                            <p class="text-xs text-muted-foreground mt-0.5">posts & reactions · past 8 months</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-6 pb-6 pt-4">
                    <div class="h-64">
                        <Bar :data="chartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-4">
                <!-- Weekly goal -->
                <Card class="rounded-3xl border-border/60 gap-0 p-5 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div class="text-[11px] uppercase tracking-widest text-muted-foreground font-medium">Weekly goal</div>
                        <span class="text-[11px] text-muted-foreground">resets Sunday</span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="font-sans text-4xl font-semibold tracking-tight leading-none">
                            {{ weeklyGoal.progress }}
                        </span>
                        <span class="text-muted-foreground text-xl font-medium">/{{ weeklyGoal.target }}</span>
                        <span class="ml-auto font-sans text-base font-semibold text-rust">{{ goalPct }}%</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-secondary">
                        <div
                            class="h-full rounded-full transition-all duration-700 ease-out"
                            :style="{ width: goalPct + '%', background: 'linear-gradient(90deg, oklch(0.57 0.17 35), oklch(0.65 0.18 20))' }"
                        ></div>
                    </div>
                    <div class="mt-2 text-[11px] text-muted-foreground">{{ weeklyGoal.target - weeklyGoal.progress }} posts to go</div>
                </Card>

                <!-- Quick actions -->
                <Card class="rounded-3xl border-border/60 gap-0 p-5 transition-all duration-200 hover:shadow-md">
                    <div class="text-[11px] uppercase tracking-widest text-muted-foreground font-medium mb-3">Quick actions</div>
                    <div class="flex flex-col gap-2">
                        <Button as-child class="rounded-2xl bg-ink text-paper hover:bg-ink/85 justify-start gap-2 h-10 transition-all duration-150 hover:shadow-sm">
                            <Link href="/feed">
                                <Pencil class="size-4" /> Write a post
                            </Link>
                        </Button>
                        <Button as-child variant="outline" class="rounded-2xl justify-start gap-2 h-10 transition-all duration-150 hover:bg-secondary/80">
                            <Link href="/posts/mine">
                                <Send class="size-4" /> My posts
                            </Link>
                        </Button>
                    </div>
                </Card>
            </div>
        </section>

        <!-- Recent posts + recent activity -->
        <section class="grid gap-4 lg:grid-cols-2">
            <Card class="rounded-3xl border-border/60 gap-0 transition-all duration-200 hover:shadow-md">
                <CardHeader class="px-6 pt-6 pb-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">My recent posts</CardTitle>
                            <p class="text-xs text-muted-foreground mt-0.5">latest published</p>
                        </div>
                        <Link href="/posts/mine" class="text-xs font-medium text-rust hover:text-rust/80 transition-colors">View all &rarr;</Link>
                    </div>
                </CardHeader>
                <CardContent class="px-6 pb-6 pt-3">
                    <ul v-if="myPosts.length" class="space-y-0.5">
                        <li
                            v-for="p in myPosts"
                            :key="p.uuid"
                            class="group/item flex items-start gap-3 rounded-2xl px-3 py-2.5 -mx-3 transition-colors duration-150 hover:bg-secondary/50"
                        >
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-rust/10 text-rust transition-colors group-hover/item:bg-rust/20">
                                <Newspaper class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Link :href="`/posts/${p.uuid}`" class="truncate text-sm font-medium hover:text-rust transition-colors">
                                        {{ truncate(p.body) }}
                                    </Link>
                                    <Badge
                                        :class="[
                                            'shrink-0 rounded-full border-0 px-2 py-0.5 text-[10px] font-medium',
                                            p.status === 'flagged' ? 'bg-rust/15 text-rust'
                                            : p.status === 'hidden' ? 'bg-ink/10 text-ink'
                                            : 'bg-moss/15 text-moss',
                                        ]"
                                    >{{ p.status }}</Badge>
                                </div>
                                <div class="mt-1 flex items-center gap-3 text-[11px] text-muted-foreground">
                                    <span class="inline-flex items-center gap-1"><Heart class="size-3" /> {{ p.likes_count }}</span>
                                    <span class="inline-flex items-center gap-1"><MessageSquare class="size-3" /> {{ p.comments_count }}</span>
                                    <span class="ml-auto">{{ timeAgo(p.created_at) }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                        You haven't posted yet. &nbsp;
                        <Link href="/feed" class="font-medium text-rust hover:underline">Write your first →</Link>
                    </div>
                </CardContent>
            </Card>

            <Card class="rounded-3xl border-border/60 gap-0 transition-all duration-200 hover:shadow-md">
                <CardHeader class="px-6 pt-6 pb-0">
                    <div>
                        <CardTitle class="text-base">Recent activity</CardTitle>
                        <p class="text-xs text-muted-foreground mt-0.5">comments on your posts</p>
                    </div>
                </CardHeader>
                <CardContent class="px-6 pb-6 pt-3">
                    <ul v-if="recentActivity.length" class="space-y-0.5">
                        <li
                            v-for="a in recentActivity"
                            :key="a.uuid"
                            class="group/item flex items-start gap-3 rounded-2xl px-3 py-2.5 -mx-3 transition-colors duration-150 hover:bg-secondary/50"
                        >
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-secondary text-ink transition-colors group-hover/item:bg-secondary/80">
                                <MessageSquare class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate">{{ truncate(a.body, 50) }}</div>
                                <div class="mt-0.5 flex items-center gap-2 text-[11px] text-muted-foreground">
                                    <span>{{ a.user?.name ?? 'unknown' }}</span>
                                    <span class="ml-auto">{{ timeAgo(a.created_at) }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                        No activity on your posts yet.
                    </div>
                </CardContent>
            </Card>
        </section>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import Badge from '@/components/ui/Badge.vue';
import { Button } from '@/components/ui/button';
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
            borderRadius: 8,
            barPercentage: 0.65,
            categoryPercentage: 0.7,
        },
        {
            label: 'Reactions',
            data: props.engagementSeries.map((d) => d.reactions),
            backgroundColor: 'oklch(0.57 0.17 35)',
            borderRadius: 8,
            barPercentage: 0.65,
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
        tooltip: {
            intersect: false,
            mode: 'index',
            padding: 10,
            cornerRadius: 10,
            titleFont: { size: 12 },
            bodyFont: { size: 11 },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: 'oklch(0.5 0.018 65)', font: { size: 10 } },
        },
        y: {
            grid: { color: 'oklch(0.82 0.02 75 / 0.35)' },
            border: { display: false, dash: [4, 4] },
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
