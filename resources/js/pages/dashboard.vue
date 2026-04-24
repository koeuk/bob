<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    Bookmark, CheckCircle2, Clock, Filter, Heart, Image, MessageCircle,
    MoreHorizontal, Pencil, Plus, Search, Send, Sparkles, TrendingUp,
} from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs = [];
const page = usePage();

const hour = new Date().getHours();
const greeting = computed(() => {
    if (hour < 5) return 'Still up';
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    if (hour < 21) return 'Good evening';
    return 'Night owl';
});
const firstName = computed(() => page.props.auth.user?.name?.split(' ')[0] ?? 'friend');

const primary = {
    label: 'Community reach',
    value: '1,284',
    delta: '+5.2% than last week',
};

const statGrid = [
    { label: 'Posts this week', value: '24',    delta: '+12%', highlight: true,  icon: Pencil },
    { label: 'Reactions',       value: '3,712', delta: '+208', highlight: false, icon: Heart },
    { label: 'Comments',        value: '512',   delta: '+41',  highlight: false, icon: MessageCircle },
    { label: 'New followers',   value: '47',    delta: '+3',   highlight: false, icon: Sparkles },
];

const chart = [
    { m: 'Jan', a: 24, b: 18 },
    { m: 'Feb', a: 42, b: 30 },
    { m: 'Mar', a: 28, b: 22 },
    { m: 'Apr', a: 48, b: 34 },
    { m: 'May', a: 35, b: 25 },
    { m: 'Jun', a: 46, b: 36 },
    { m: 'Jul', a: 38, b: 28 },
    { m: 'Aug', a: 52, b: 40 },
];
const chartMax = 60;

const goal = { current: 12, target: 20, label: 'Weekly post goal' };
const goalPct = Math.round((goal.current / goal.target) * 100);

const pages = [
    { tone: 'dark',   title: 'Notes on a quiet life', posts: 42, handle: '@quiet' },
    { tone: 'accent', title: 'Field journal',         posts: 18, handle: '@field' },
];

const activity = [
    { id: 'ACT-0076', kind: 'Post',     author: 'Hazel B.',     status: 'published', time: '2h ago',    tone: 'moss',   icon: Pencil },
    { id: 'ACT-0075', kind: 'Comment',  author: 'Orson P.',     status: 'pending',   time: '3h ago',    tone: 'accent', icon: MessageCircle },
    { id: 'ACT-0074', kind: 'Follow',   author: 'Maira Kalman', status: 'accepted',  time: '5h ago',    tone: 'moss',   icon: Sparkles },
    { id: 'ACT-0073', kind: 'Bookmark', author: 'Anne Carson',  status: 'saved',     time: '8h ago',    tone: 'muted',  icon: Bookmark },
    { id: 'ACT-0072', kind: 'Report',   author: 'System',       status: 'resolved',  time: 'Yesterday', tone: 'moss',   icon: CheckCircle2 },
];
</script>

<template>
    <Head title="Overview" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Greeting -->
        <section class="mb-6">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                {{ greeting }}, {{ firstName }}
            </h1>
            <p class="mt-1.5 text-sm text-muted-foreground">
                Stay on top of your posts, monitor reach, and follow the conversation.
            </p>
        </section>

        <!-- TOP ROW -->
        <section class="grid gap-5 lg:grid-cols-12">
            <!-- Big stat card with actions + pages -->
            <div class="rounded-[28px] bg-card p-6 shadow-sm lg:col-span-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ primary.label }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                        <span class="size-1.5 rounded-full bg-primary"></span>
                        followers
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-4xl font-semibold tracking-tight">{{ primary.value }}</div>
                    <div class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        <TrendingUp class="size-3.5" />
                        {{ primary.delta }}
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-foreground text-sm font-medium text-background hover:opacity-90 transition">
                        <Pencil class="size-4" />
                        New post
                    </button>
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-full border border-border bg-background text-sm font-medium hover:bg-muted transition">
                        <Send class="size-4" />
                        Invite
                    </button>
                </div>

                <div class="mt-5 rounded-2xl bg-muted/60 p-3">
                    <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                        <span>My pages &middot; {{ pages.length }} active</span>
                        <button class="text-foreground hover:text-primary">View all</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="p in pages"
                            :key="p.handle"
                            :class="[
                                'relative overflow-hidden rounded-xl p-3',
                                p.tone === 'dark' ? 'bg-foreground text-background' : 'bg-accent text-accent-foreground',
                            ]"
                        >
                            <div class="mb-4 flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider opacity-80">
                                <span class="size-1.5 rounded-full bg-current"></span>
                                active
                            </div>
                            <div class="text-sm font-medium leading-tight">{{ p.title }}</div>
                            <div class="mt-1 text-[10px] opacity-70">{{ p.handle }} &middot; {{ p.posts }} posts</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2x2 stat grid -->
            <div class="grid grid-cols-2 gap-3 lg:col-span-4">
                <div
                    v-for="s in statGrid"
                    :key="s.label"
                    :class="[
                        'relative overflow-hidden rounded-[22px] p-5 transition',
                        s.highlight
                            ? 'bg-accent text-accent-foreground shadow-md shadow-accent/20'
                            : 'bg-card shadow-sm hover:shadow',
                    ]"
                >
                    <div
                        v-if="s.highlight"
                        aria-hidden="true"
                        class="pointer-events-none absolute inset-0 opacity-[0.12]"
                        style="background-image: repeating-linear-gradient(45deg, white 0, white 1px, transparent 1px, transparent 10px);"
                    ></div>

                    <div class="relative flex items-start justify-between">
                        <span :class="['text-sm font-medium', s.highlight ? 'opacity-90' : 'text-muted-foreground']">
                            {{ s.label }}
                        </span>
                        <span
                            :class="[
                                'flex size-7 items-center justify-center rounded-full',
                                s.highlight ? 'bg-white/20' : 'bg-muted',
                            ]"
                        >
                            <component :is="s.icon" :class="['size-3.5', s.highlight ? 'text-accent-foreground' : 'text-foreground']" />
                        </span>
                    </div>
                    <div class="relative mt-6">
                        <div class="text-3xl font-semibold tracking-tight">{{ s.value }}</div>
                        <div :class="['mt-1 text-xs font-medium', s.highlight ? 'opacity-80' : 'text-primary']">
                            {{ s.delta }} this month
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart card -->
            <div class="rounded-[28px] bg-card p-6 shadow-sm lg:col-span-4">
                <div class="mb-1 flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-semibold">Total engagement</h3>
                        <p class="text-xs text-muted-foreground">Posts &amp; reactions over the past 8 months</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                            <span class="size-2 rounded-sm bg-foreground"></span> Posts
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                            <span class="size-2 rounded-sm bg-accent"></span> Reactions
                        </span>
                    </div>
                </div>

                <div class="mt-5 flex h-[180px] items-end gap-3 sm:gap-4">
                    <div v-for="c in chart" :key="c.m" class="flex flex-1 flex-col items-center gap-2">
                        <div class="flex h-[140px] w-full items-end justify-center gap-1">
                            <div
                                class="w-2/5 rounded-t-md bg-foreground"
                                :style="{ height: (c.a / chartMax * 100) + '%' }"
                            ></div>
                            <div
                                class="w-2/5 rounded-t-md bg-accent"
                                :style="{ height: (c.b / chartMax * 100) + '%' }"
                            ></div>
                        </div>
                        <span class="text-[10px] text-muted-foreground">{{ c.m }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- BOTTOM ROW -->
        <section class="mt-5 grid gap-5 lg:grid-cols-12">
            <!-- Goal -->
            <div class="rounded-[28px] bg-card p-6 shadow-sm lg:col-span-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold">{{ goal.label }}</h3>
                    <span class="text-xs text-muted-foreground">resets Sunday</span>
                </div>
                <div class="mb-3 flex items-baseline justify-between">
                    <div>
                        <span class="text-3xl font-semibold tracking-tight">{{ goal.current }}</span>
                        <span class="text-base text-muted-foreground"> / {{ goal.target }}</span>
                    </div>
                    <span class="text-sm font-medium text-primary">{{ goalPct }}%</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-muted">
                    <div class="h-full rounded-full bg-accent transition-all" :style="{ width: goalPct + '%' }"></div>
                </div>
                <div class="mt-6 flex items-center gap-2">
                    <button class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-full border border-border bg-background text-sm font-medium hover:bg-muted transition">
                        <Plus class="size-4" />
                        Add draft
                    </button>
                    <button class="inline-flex size-10 items-center justify-center rounded-full border border-border bg-background hover:bg-muted transition">
                        <Image class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Activity table -->
            <div class="rounded-[28px] bg-card p-6 shadow-sm lg:col-span-8">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-base font-semibold">Recent activity</h3>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="search"
                                placeholder="Search"
                                class="h-9 w-48 rounded-full border border-border bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground/70 focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 transition"
                            />
                        </div>
                        <button class="inline-flex h-9 items-center gap-2 rounded-full border border-border bg-background px-3 text-sm hover:bg-muted transition">
                            <Filter class="size-4" />
                            Filter
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-muted-foreground">
                                <th class="pb-3 pr-3 font-medium"><input type="checkbox" class="size-3.5 accent-foreground" /></th>
                                <th class="pb-3 pr-4 font-medium">Ref</th>
                                <th class="pb-3 pr-4 font-medium">Activity</th>
                                <th class="pb-3 pr-4 font-medium">Author</th>
                                <th class="pb-3 pr-4 font-medium">Status</th>
                                <th class="pb-3 pr-2 font-medium">When</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-for="a in activity" :key="a.id" class="group transition hover:bg-muted/50">
                                <td class="py-3 pr-3"><input type="checkbox" class="size-3.5 accent-foreground" /></td>
                                <td class="py-3 pr-4 text-xs text-muted-foreground">{{ a.id }}</td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            :class="[
                                                'flex size-7 items-center justify-center rounded-lg',
                                                a.tone === 'moss'   && 'bg-primary/10 text-primary',
                                                a.tone === 'accent' && 'bg-accent/10 text-accent',
                                                a.tone === 'muted'  && 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <component :is="a.icon" class="size-3.5" />
                                        </span>
                                        <span class="font-medium">{{ a.kind }}</span>
                                    </div>
                                </td>
                                <td class="py-3 pr-4">{{ a.author }}</td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center gap-1.5 text-xs">
                                        <span
                                            :class="[
                                                'size-1.5 rounded-full',
                                                a.status === 'pending' ? 'bg-accent' : 'bg-primary',
                                            ]"
                                        ></span>
                                        <span class="capitalize">{{ a.status }}</span>
                                    </span>
                                </td>
                                <td class="py-3 pr-2 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1"><Clock class="size-3" />{{ a.time }}</span>
                                </td>
                                <td class="py-3 text-right">
                                    <button class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground opacity-0 transition hover:bg-muted hover:text-foreground group-hover:opacity-100">
                                        <MoreHorizontal class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
