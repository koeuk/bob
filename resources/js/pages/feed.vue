<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Flag, Heart, MessageCircle, Send } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
});

const page = usePage();
const me = computed(() => page.props.auth?.user);

const composer = useForm({ body: '' });
const submitPost = () => composer.post('/posts', {
    preserveScroll: true,
    onSuccess: () => composer.reset('body'),
});

const toggleLike = (post) => {
    router.post(`/posts/${post.uuid}/like`, {}, { preserveScroll: true });
};

const reportTarget = ref(null);
const reportForm = useForm({ type: 'post', target_uuid: '', reason: '' });
const openReport = (post) => {
    reportForm.type = 'post';
    reportForm.target_uuid = post.uuid;
    reportForm.reason = '';
    reportTarget.value = post;
};
const submitReport = () => reportForm.post('/reports', {
    preserveScroll: true,
    onSuccess: () => { reportTarget.value = null; reportForm.reset(); },
});

const dateFmt = (iso) => {
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const truncate = (t, n = 320) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;
</script>

<template>
    <Head title="Feed" />
    <AppLayout>
        <section class="pt-2">
            <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">Feed</h1>
            <p class="mt-1 text-sm text-muted-foreground">What the room is writing today.</p>
        </section>

        <!-- Composer -->
        <form class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm" @submit.prevent="submitPost">
            <div class="flex gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-semibold text-paper">
                    {{ initials(me?.name) }}
                </span>
                <div class="flex-1">
                    <textarea
                        v-model="composer.body"
                        rows="3"
                        class="w-full resize-none rounded-2xl bg-secondary/60 p-3 text-sm leading-relaxed outline-none focus:bg-secondary"
                        placeholder="Write a dispatch..."
                        maxlength="10000"
                    />
                    <p v-if="composer.errors.body" class="mt-1 text-xs text-destructive">{{ composer.errors.body }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[11px] text-muted-foreground">{{ composer.body.length }}/10000</span>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                            :disabled="!composer.body.trim() || composer.processing"
                        >
                            <Send class="size-4" /> Post
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Posts -->
        <div v-if="posts.data.length" class="space-y-3">
            <article
                v-for="p in posts.data"
                :key="p.uuid"
                class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm"
            >
                <header class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-full bg-secondary text-sm font-semibold">
                            {{ initials(p.user?.name) }}
                        </span>
                        <div>
                            <div class="text-sm font-medium">{{ p.user?.name ?? 'unknown' }}</div>
                            <div class="text-[11px] text-muted-foreground">{{ dateFmt(p.created_at) }}</div>
                        </div>
                    </div>
                    <button
                        class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-rust"
                        title="Report this post"
                        @click="openReport(p)"
                    >
                        <Flag class="size-4" />
                    </button>
                </header>

                <Link :href="`/posts/${p.uuid}`" class="mt-3 block">
                    <p class="whitespace-pre-wrap text-[15px] leading-relaxed hover:text-rust">{{ truncate(p.body) }}</p>
                </Link>

                <footer class="mt-4 flex items-center gap-1 text-sm">
                    <button
                        class="group inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm hover:bg-rust/10"
                        @click="toggleLike(p)"
                    >
                        <Heart
                            :class="[
                                'size-4 transition-colors',
                                p.liked_by_me ? 'fill-rust text-rust' : 'text-muted-foreground group-hover:text-rust',
                            ]"
                        />
                        <span :class="p.liked_by_me ? 'text-rust' : 'text-muted-foreground'">
                            {{ p.likes_count }}
                        </span>
                    </button>
                    <Link
                        :href="`/posts/${p.uuid}`"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm text-muted-foreground hover:bg-secondary"
                    >
                        <MessageCircle class="size-4" />
                        {{ p.comments_count }}
                    </Link>
                </footer>
            </article>
        </div>

        <div v-else class="rounded-3xl border border-border/60 bg-card py-16 text-center text-sm text-muted-foreground shadow-sm">
            Nothing yet. Be the first to write.
        </div>

        <!-- Pagination -->
        <div v-if="posts.data.length && posts.links.length > 3" class="flex items-center justify-between text-xs text-muted-foreground">
            <span>Showing {{ posts.from }}–{{ posts.to }} of {{ posts.total }}</span>
            <div class="flex items-center gap-1">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs',
                        link.active ? 'bg-ink text-paper' : link.url ? 'hover:bg-secondary' : 'opacity-40',
                    ]"
                    preserve-scroll
                    preserve-state
                />
            </div>
        </div>

        <!-- Report modal -->
        <Teleport to="body">
            <div v-if="reportTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">Report this post</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        by {{ reportTarget.user?.name }}. A moderator will review.
                    </p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitReport">
                        <textarea
                            v-model="reportForm.reason"
                            rows="4"
                            class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                            placeholder="Why should this be reviewed?"
                            required
                        />
                        <p v-if="reportForm.errors.reason" class="text-xs text-destructive">{{ reportForm.errors.reason }}</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="rounded-full px-4 py-2 text-sm hover:bg-secondary" @click="reportTarget = null">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="rounded-full bg-rust px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
                                :disabled="reportForm.processing"
                            >
                                Submit report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
