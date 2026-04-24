<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Flag, Heart, MessageCircle, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
    comments: { type: Array, default: () => [] },
    isAuthor: { type: Boolean, default: false },
});

const page = usePage();
const me = computed(() => page.props.auth?.user);
const isModerator = computed(() => ['moderator', 'admin', 'super_admin'].includes(me.value?.role));

const dateFmt = (iso) => {
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return `${Math.floor(diff)}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const togglePostLike = () => router.post(`/posts/${props.post.uuid}/like`, {}, { preserveScroll: true });
const toggleCommentLike = (c) => router.post(`/comments/${c.uuid}/like`, {}, { preserveScroll: true });

const deletePost = () => {
    if (!window.confirm('Delete this post? It will be soft-removed.')) return;
    router.delete(`/posts/${props.post.uuid}`);
};

const deleteComment = (c) => {
    if (!window.confirm('Delete this comment?')) return;
    router.delete(`/comments/${c.uuid}`, { preserveScroll: true });
};

const commentForm = useForm({ body: '' });
const submitComment = () => commentForm.post(`/posts/${props.post.uuid}/comments`, {
    preserveScroll: true,
    onSuccess: () => commentForm.reset('body'),
});

const reportTarget = ref(null);
const reportForm = useForm({ type: 'post', target_uuid: '', reason: '' });
const openReport = (type, uuid, label) => {
    reportForm.type = type;
    reportForm.target_uuid = uuid;
    reportForm.reason = '';
    reportTarget.value = { type, label };
};
const submitReport = () => reportForm.post('/reports', {
    preserveScroll: true,
    onSuccess: () => { reportTarget.value = null; reportForm.reset(); },
});
</script>

<template>
    <Head :title="`${post.user?.name} · post`" />
    <AppLayout>
        <Link href="/feed" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to feed
        </Link>

        <!-- Post -->
        <article class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <header class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-full bg-ink text-sm font-semibold text-paper">
                        {{ initials(post.user?.name) }}
                    </span>
                    <div>
                        <div class="font-medium">{{ post.user?.name ?? 'unknown' }}</div>
                        <div class="text-xs text-muted-foreground">{{ dateFmt(post.created_at) }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        v-if="!isAuthor"
                        class="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-rust"
                        title="Report"
                        @click="openReport('post', post.uuid, 'this post')"
                    >
                        <Flag class="size-4" />
                    </button>
                    <button
                        v-if="isAuthor || isModerator"
                        class="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        title="Delete"
                        @click="deletePost"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </header>

            <div class="mt-4 whitespace-pre-wrap text-base leading-relaxed">{{ post.body }}</div>

            <footer class="mt-5 flex items-center gap-1 text-sm">
                <button
                    class="group inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 hover:bg-rust/10"
                    @click="togglePostLike"
                >
                    <Heart
                        :class="[
                            'size-4',
                            post.liked_by_me ? 'fill-rust text-rust' : 'text-muted-foreground group-hover:text-rust',
                        ]"
                    />
                    <span :class="post.liked_by_me ? 'text-rust' : 'text-muted-foreground'">
                        {{ post.likes_count }}
                    </span>
                </button>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-muted-foreground">
                    <MessageCircle class="size-4" /> {{ comments.length }}
                </span>
            </footer>
        </article>

        <!-- Composer -->
        <form class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm" @submit.prevent="submitComment">
            <div class="flex gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-paper">
                    {{ initials(me?.name) }}
                </span>
                <div class="flex-1">
                    <textarea
                        v-model="commentForm.body"
                        rows="2"
                        class="w-full resize-none rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                        placeholder="Write a reply..."
                        maxlength="5000"
                    />
                    <p v-if="commentForm.errors.body" class="mt-1 text-xs text-destructive">{{ commentForm.errors.body }}</p>
                    <div class="mt-2 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                            :disabled="!commentForm.body.trim() || commentForm.processing"
                        >
                            <Send class="size-4" /> Reply
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Comments -->
        <section v-if="comments.length" class="space-y-3">
            <h3 class="text-sm font-medium text-muted-foreground">
                Replies · {{ comments.length }}
            </h3>
            <div
                v-for="c in comments"
                :key="c.uuid"
                class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm"
            >
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-full bg-secondary text-xs font-semibold">
                            {{ initials(c.user?.name) }}
                        </span>
                        <div>
                            <div class="text-sm font-medium">{{ c.user?.name ?? 'unknown' }}</div>
                            <div class="text-[11px] text-muted-foreground">{{ dateFmt(c.created_at) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button
                            v-if="c.user_id !== me?.id && c.user?.uuid !== me?.uuid"
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-rust"
                            title="Report"
                            @click="openReport('comment', c.uuid, 'this comment')"
                        >
                            <Flag class="size-3.5" />
                        </button>
                        <button
                            v-if="c.user_id === me?.id || isModerator"
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                            title="Delete"
                            @click="deleteComment(c)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>
                </header>
                <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed">{{ c.body }}</p>
                <div class="mt-2">
                    <button
                        class="group inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs hover:bg-rust/10"
                        @click="toggleCommentLike(c)"
                    >
                        <Heart
                            :class="[
                                'size-3.5',
                                c.liked_by_me ? 'fill-rust text-rust' : 'text-muted-foreground group-hover:text-rust',
                            ]"
                        />
                        <span :class="c.liked_by_me ? 'text-rust' : 'text-muted-foreground'">
                            {{ c.likes_count }}
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <div v-else class="rounded-3xl border border-border/60 bg-card py-10 text-center text-sm text-muted-foreground shadow-sm">
            No replies yet. Say something.
        </div>

        <!-- Report modal -->
        <Teleport to="body">
            <div v-if="reportTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">Report {{ reportTarget.label }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">A moderator will review.</p>
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
