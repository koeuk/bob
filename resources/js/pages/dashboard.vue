<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Bookmark, Heart, MessageCircle, Pencil, Share2 } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];
const page = usePage();

const hour = new Date().getHours();
const greeting = computed(() => {
    if (hour < 5) return 'Still awake';
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    if (hour < 21) return 'Good evening';
    return 'Late for bed';
});

const stats = [
    { label: 'Followers', value: '1,284', delta: '+12 this week', tone: 'moss' },
    { label: 'Posts', value: '47', delta: '3 drafted', tone: 'ink' },
    { label: 'Reactions', value: '3,712', delta: '+208 today', tone: 'rust' },
    { label: 'Unread', value: '12', delta: 'in inbox', tone: 'ink' },
];

const dispatches = [
    {
        kind: 'Dispatch',
        time: '2h ago',
        title: 'On quiet rooms and loud timelines.',
        lede: 'A small meditation on why we built bob the way we did, and why silence is its own kind of conversation.',
        author: 'Bob Editors',
        reactions: 128,
        comments: 23,
    },
    {
        kind: 'From a friend',
        time: '5h ago',
        title: 'Recipe notes, scribbled on a receipt.',
        lede: 'Hazel shared a half-remembered tarte tatin from her grandmother. It may or may not have four apples.',
        author: 'Hazel B.',
        reactions: 54,
        comments: 9,
    },
    {
        kind: 'Bulletin',
        time: '1d ago',
        title: 'The week in passing.',
        lede: 'Twelve people wrote to you. Seven were strangers. Two became friends.',
        author: 'Bob Letter',
        reactions: 302,
        comments: 41,
    },
];

const trending = [
    { tag: 'slowmornings', count: '2,481 dispatches' },
    { tag: 'onwriting', count: '1,190 dispatches' },
    { tag: 'fieldnotes', count: '843 dispatches' },
    { tag: 'kitchen', count: '612 dispatches' },
    { tag: 'lateshift', count: '401 dispatches' },
];

const people = [
    { name: 'Cal Newport', handle: '@cal', mutual: 'and 3 friends' },
    { name: 'Maira Kalman', handle: '@maira', mutual: 'and 8 friends' },
    { name: 'Orson P.', handle: '@orsonp', mutual: 'reads what you read' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- HERO: editorial greeting -->
        <section class="mb-16">
            <div class="mb-6 flex items-baseline gap-3">
                <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-rust">&sect; front page</span>
                <span class="h-px flex-1 bg-hairline"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
                    opening remarks
                </span>
            </div>

            <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-end">
                <h1 class="font-serif text-6xl leading-[0.95] tracking-tight sm:text-7xl lg:text-8xl">
                    {{ greeting }},<br />
                    <span class="italic text-moss">
                        {{ page.props.auth.user?.name?.split(' ')[0] ?? 'friend' }}
                    </span><span class="text-rust">.</span>
                </h1>
                <p class="max-w-sm font-serif text-lg italic leading-snug text-muted-foreground lg:text-xl">
                    A quiet morning at the journal. Twelve people wrote in overnight.
                    Nothing is urgent. Everything is welcome.
                </p>
            </div>

            <!-- quick actions -->
            <div class="mt-10 flex flex-wrap items-center gap-3">
                <button class="group inline-flex items-center gap-3 bg-ink px-5 py-3 text-paper transition-colors hover:bg-moss">
                    <Pencil class="size-4" />
                    <span class="font-serif text-base">Write a dispatch</span>
                    <ArrowUpRight class="size-3.5 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                </button>
                <button class="group inline-flex items-center gap-3 rule-t rule-b rule-l rule-r px-5 py-3 text-ink hover:border-ink">
                    <Share2 class="size-4" />
                    <span class="font-serif text-base">Share a photo</span>
                </button>
                <button class="group inline-flex items-center gap-3 rule-t rule-b rule-l rule-r px-5 py-3 text-ink hover:border-ink">
                    <Bookmark class="size-4" />
                    <span class="font-serif text-base">Bookmarks</span>
                </button>
            </div>
        </section>

        <!-- STATS: editorial figures -->
        <section class="mb-16">
            <div class="mb-4 flex items-baseline gap-3">
                <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-rust">&sect; figures</span>
                <span class="h-px flex-1 bg-hairline"></span>
            </div>
            <div class="grid grid-cols-2 gap-0 rule-t rule-b md:grid-cols-4">
                <div
                    v-for="(s, i) in stats"
                    :key="s.label"
                    :class="['flex flex-col gap-2 px-6 py-6', i > 0 && 'md:rule-l']"
                >
                    <span class="font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
                        {{ s.label }}
                    </span>
                    <span
                        :class="[
                            'font-serif text-5xl leading-none tracking-tight',
                            s.tone === 'moss' && 'text-moss',
                            s.tone === 'rust' && 'text-rust',
                        ]"
                    >
                        {{ s.value }}
                    </span>
                    <span class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">
                        {{ s.delta }}
                    </span>
                </div>
            </div>
        </section>

        <!-- FEED + SIDEBAR -->
        <section class="grid gap-12 lg:grid-cols-[1.6fr_1fr]">
            <!-- Main: recent dispatches -->
            <div>
                <div class="mb-4 flex items-baseline gap-3">
                    <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-rust">&sect; recent dispatches</span>
                    <span class="h-px flex-1 bg-hairline"></span>
                    <Link href="/feed" class="font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground hover:text-ink">
                        View all &rarr;
                    </Link>
                </div>

                <article
                    v-for="(d, i) in dispatches"
                    :key="i"
                    :class="[
                        'group relative py-8 transition-colors',
                        i < dispatches.length - 1 ? 'rule-b' : '',
                        i === 0 ? 'rule-t' : '',
                    ]"
                >
                    <div class="mb-2 flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
                        <span class="text-rust">{{ d.kind }}</span>
                        <span>&middot;</span>
                        <span>{{ d.time }}</span>
                    </div>
                    <h3 class="font-serif text-3xl leading-tight tracking-tight group-hover:text-moss transition-colors sm:text-4xl">
                        <Link href="#">{{ d.title }}</Link>
                    </h3>
                    <p class="mt-3 max-w-2xl font-serif text-lg italic leading-snug text-muted-foreground">
                        {{ d.lede }}
                    </p>
                    <div class="mt-5 flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
                        <span>&mdash; {{ d.author }}</span>
                        <span class="flex items-center gap-5">
                            <span class="inline-flex items-center gap-1.5">
                                <Heart class="size-3" />
                                {{ d.reactions }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <MessageCircle class="size-3" />
                                {{ d.comments }}
                            </span>
                        </span>
                    </div>
                </article>
            </div>

            <!-- Sidebar: trending + people -->
            <aside class="space-y-12 lg:sticky lg:top-10 lg:self-start">
                <div>
                    <div class="mb-4 flex items-baseline gap-3">
                        <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-rust">&sect; trending</span>
                        <span class="h-px flex-1 bg-hairline"></span>
                    </div>
                    <ol class="rule-t rule-b divide-y divide-hairline">
                        <li
                            v-for="(t, i) in trending"
                            :key="t.tag"
                            class="flex items-baseline justify-between gap-3 py-4"
                        >
                            <div class="flex items-baseline gap-3">
                                <span class="font-mono text-[10px] tracking-[0.2em] text-muted-foreground">
                                    {{ String(i + 1).padStart(2, '0') }}
                                </span>
                                <div>
                                    <div class="font-serif text-xl leading-tight tracking-tight">
                                        #{{ t.tag }}
                                    </div>
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">
                                        {{ t.count }}
                                    </div>
                                </div>
                            </div>
                            <ArrowUpRight class="size-3.5 text-muted-foreground" />
                        </li>
                    </ol>
                </div>

                <div>
                    <div class="mb-4 flex items-baseline gap-3">
                        <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-rust">&sect; people you may know</span>
                        <span class="h-px flex-1 bg-hairline"></span>
                    </div>
                    <ul class="rule-t rule-b divide-y divide-hairline">
                        <li
                            v-for="p in people"
                            :key="p.handle"
                            class="flex items-center justify-between gap-4 py-4"
                        >
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-full border border-hairline font-serif text-base text-muted-foreground">
                                    {{ p.name[0] }}
                                </span>
                                <div>
                                    <div class="font-serif text-lg leading-tight">{{ p.name }}</div>
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">
                                        {{ p.handle }} &middot; {{ p.mutual }}
                                    </div>
                                </div>
                            </div>
                            <button class="rule-t rule-b rule-l rule-r px-3 py-1 font-mono text-[10px] uppercase tracking-[0.22em] hover:border-ink hover:text-ink transition-colors">
                                Follow
                            </button>
                        </li>
                    </ul>
                </div>
            </aside>
        </section>
    </AppLayout>
</template>
