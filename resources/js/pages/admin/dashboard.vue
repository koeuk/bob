<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
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
    Flag,
    Hash,
    MessageSquare,
    Newspaper,
    ShieldBan,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    stats: { type: Object, required: true },
    series: { type: Object, required: true },
    recentReports: { type: Array, default: () => [] },
    recentActivity: { type: Array, default: () => [] },
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

const chartLabels = computed(() => {
    const map = new Map(props.series.signups.map((d) => [d.date, d.count]));
    const posts = new Map(props.series.posts.map((d) => [d.date, d.count]));
    const keys = Array.from(new Set([...map.keys(), ...posts.keys()])).sort();
    return {
        labels: keys.map((k) => new Date(k).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        signups: keys.map((k) => map.get(k) ?? 0),
        posts: keys.map((k) => posts.get(k) ?? 0),
    };
});

const chartData = computed(() => ({
    labels: chartLabels.value.labels,
    datasets: [
        {
            label: 'Signups',
            data: chartLabels.value.signups,
            backgroundColor: 'oklch(0.57 0.17 35)',
            borderRadius: 6,
            barPercentage: 0.7,
        },
        {
            label: 'Posts',
            data: chartLabels.value.posts,
            backgroundColor: 'oklch(0.22 0.012 60)',
            borderRadius: 6,
            barPercentage: 0.7,
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

const figures = computed(() => [
    { key: 'users_total', label: 'Total Users', value: props.stats.users_total, sub: `${props.stats.users_today} today`, icon: Users, trend: 'up' },
    { key: 'posts_total', label: 'Posts', value: props.stats.posts_total, sub: `${props.stats.posts_today} today`, icon: Newspaper, trend: 'up' },
    { key: 'comments_total', label: 'Comments', value: props.stats.comments_total, sub: 'all time', icon: MessageSquare, trend: 'up' },
    { key: 'bans_active', label: 'Active Bans', value: props.stats.bans_active, sub: 'currently', icon: ShieldBan, trend: 'down' },
]);

const fmt = (n) => new Intl.NumberFormat('en-US').format(n ?? 0);

const reportableTitle = (r) => {
    const t = r.reportable_type?.split('\\').pop() ?? 'Item';
    return `${t} report`;
};

const timeAgo = (iso) => {
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <!-- Greeting -->
        <section class="pt-2">
            <h1 class="font-sans text-4xl font-semibold tracking-tight sm:text-5xl">
                {{ greeting }}, <span class="text-rust">{{ firstName }}</span>
            </h1>
            <p class="mt-2 max-w-xl text-sm text-muted-foreground">
                Stay on top of moderation, monitor growth, and review the room.
            </p>
        </section>

        <!-- Top grid: pending reports hero + figures + chart -->
        <section class="grid gap-4 lg:grid-cols-3">
            <!-- Pending reports hero card (rust accent) -->
            <Link
                href="/admin/reports?filter[status]=pending"
                class="group relative overflow-hidden rounded-3xl bg-rust p-6 text-paper shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-paper/20 backdrop-blur">
                        <Flag class="size-5" />
                    </span>
                    <ArrowUpRight class="size-5 opacity-70 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </div>
                <div class="mt-8">
                    <div class="text-sm uppercase tracking-wide opacity-80">Pending Reports</div>
                    <div class="mt-1 font-sans text-5xl font-semibold tracking-tight">{{ fmt(stats.reports_pending) }}</div>
                    <div class="mt-1 text-xs opacity-80">awaiting review</div>
                </div>
                <div class="absolute -bottom-10 -right-10 size-40 rounded-full bg-paper/10 blur-2xl"></div>
            </Link>

            <!-- Figures grid -->
            <div class="grid grid-cols-2 gap-4 lg:col-span-2 lg:grid-cols-2">
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

        <!-- Chart + active snapshot -->
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Signups vs Posts</h3>
                        <p class="text-xs text-muted-foreground">last 30 days</p>
                    </div>
                </div>
                <div class="h-72">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground">Active now</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="font-sans text-4xl font-semibold tracking-tight">{{ fmt(stats.users_active_5m) }}</span>
                        <span class="inline-flex items-center gap-1 text-xs text-moss">
                            <span class="size-1.5 animate-pulse rounded-full bg-moss"></span>
                            live (5m)
                        </span>
                    </div>
                </div>
                <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground">New today</div>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[11px] text-muted-foreground">Users</div>
                            <div class="font-sans text-2xl font-semibold">{{ fmt(stats.users_today) }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-muted-foreground">Posts</div>
                            <div class="font-sans text-2xl font-semibold">{{ fmt(stats.posts_today) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent reports + activity -->
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Recent reports</h3>
                        <p class="text-xs text-muted-foreground">last 8</p>
                    </div>
                    <Link href="/admin/reports" class="text-xs font-medium text-rust hover:underline">View all &rarr;</Link>
                </div>
                <ul v-if="recentReports.length" class="divide-y divide-border/60">
                    <li v-for="r in recentReports" :key="r.uuid" class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 inline-flex size-8 items-center justify-center rounded-xl bg-rust/10 text-rust">
                            <Flag class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <Link :href="`/admin/reports/${r.uuid}`" class="truncate font-medium hover:text-rust">
                                    {{ reportableTitle(r) }}
                                </Link>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium',
                                        r.status === 'pending' ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground',
                                    ]"
                                >
                                    {{ r.status }}
                                </span>
                            </div>
                            <div class="truncate text-xs text-muted-foreground">
                                by {{ r.reporter?.name ?? 'unknown' }} &middot; {{ r.reason }}
                            </div>
                        </div>
                        <span class="shrink-0 text-[11px] text-muted-foreground">{{ timeAgo(r.created_at) }}</span>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                    No reports yet.
                </div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Recent activity</h3>
                        <p class="text-xs text-muted-foreground">admin actions</p>
                    </div>
                    <Link href="/admin/activity-logs" class="text-xs font-medium text-rust hover:underline">View all &rarr;</Link>
                </div>
                <ul v-if="recentActivity.length" class="divide-y divide-border/60">
                    <li v-for="a in recentActivity" :key="a.uuid" class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 inline-flex size-8 items-center justify-center rounded-xl bg-secondary text-ink">
                            <Hash class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium">{{ a.action }}</div>
                            <div class="truncate text-xs text-muted-foreground">
                                {{ a.admin?.name ?? 'system' }}
                            </div>
                        </div>
                        <span class="shrink-0 text-[11px] text-muted-foreground">{{ timeAgo(a.created_at) }}</span>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/50 py-10 text-center text-sm text-muted-foreground">
                    No activity yet.
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
