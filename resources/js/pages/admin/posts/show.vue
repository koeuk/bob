<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, Flag, Heart, MessageCircle, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
});

const page = usePage();

const flagForm = useForm({ status: props.post.status });
const setStatus = (newStatus) => {
    flagForm.status = newStatus;
    flagForm.patch(`/admin/posts/${props.post.uuid}/flag`, { preserveScroll: true });
};

const deletePost = () => {
    if (!window.confirm('Delete this post? It will be soft-deleted.')) return;
    router.delete(`/admin/posts/${props.post.uuid}`);
};

const dateFmt = (iso) => new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const statusTone = computed(() => {
    switch (props.post.status) {
        case 'flagged': return 'bg-rust/15 text-rust';
        case 'hidden': return 'bg-ink/10 text-ink';
        default: return 'bg-moss/15 text-moss';
    }
});
</script>

<template>
    <Head title="Post" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <Link href="/admin/posts" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
                <ArrowLeft class="size-4" /> Back to posts
            </Link>
            <Link
                :href="`/admin/posts/${post.uuid}/edit`"
                class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-medium hover:bg-secondary"
            >
                <Pencil class="size-4" /> Edit
            </Link>
        </div>

        <article class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <header class="mb-4 flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-full bg-ink text-sm font-semibold text-paper">
                        {{ initials(post.user?.name) }}
                    </span>
                    <div>
                        <Link v-if="post.user" :href="`/admin/users/${post.user.uuid}`" class="font-medium hover:text-rust">
                            {{ post.user.name }}
                        </Link>
                        <div class="text-xs text-muted-foreground">{{ dateFmt(post.created_at) }}</div>
                    </div>
                </div>
                <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium', statusTone]">{{ post.status }}</span>
            </header>

            <div class="whitespace-pre-wrap text-base leading-relaxed">{{ post.body }}</div>

            <div class="mt-6 flex items-center gap-6 text-sm text-muted-foreground">
                <span class="inline-flex items-center gap-1.5"><Heart class="size-4" /> {{ post.likes_count ?? 0 }}</span>
                <span class="inline-flex items-center gap-1.5"><MessageCircle class="size-4" /> {{ post.comments?.length ?? 0 }}</span>
                <span v-if="post.reports?.length" class="inline-flex items-center gap-1.5 text-rust"><Flag class="size-4" /> {{ post.reports.length }}</span>
            </div>
        </article>

        <!-- Moderation actions -->
        <section class="flex flex-wrap items-center gap-2 rounded-3xl border border-border/60 bg-card p-4 shadow-sm">
            <span class="mr-2 text-xs uppercase tracking-wide text-muted-foreground">Moderate</span>
            <button
                :disabled="post.status === 'active'"
                class="inline-flex items-center gap-2 rounded-full bg-moss px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                @click="setStatus('active')"
            >
                <Eye class="size-4" /> Active
            </button>
            <button
                :disabled="post.status === 'flagged'"
                class="inline-flex items-center gap-2 rounded-full bg-rust px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                @click="setStatus('flagged')"
            >
                <Flag class="size-4" /> Flag
            </button>
            <button
                :disabled="post.status === 'hidden'"
                class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                @click="setStatus('hidden')"
            >
                <EyeOff class="size-4" /> Hide
            </button>
            <div class="mx-2 h-6 w-px bg-border"></div>
            <button
                class="inline-flex items-center gap-2 rounded-full border border-destructive/40 px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/5"
                @click="deletePost"
            >
                <Trash2 class="size-4" /> Delete
            </button>
        </section>

        <!-- Reports for this post -->
        <section v-if="post.reports?.length" class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold">Reports on this post</h3>
            <ul class="divide-y divide-border/60">
                <li v-for="r in post.reports" :key="r.uuid" class="flex items-start justify-between gap-3 py-3 text-sm">
                    <div>
                        <div class="font-medium">
                            <Link :href="`/admin/reports/${r.uuid}`" class="hover:text-rust">{{ r.reason }}</Link>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            by {{ r.reporter?.name ?? 'unknown' }} · {{ dateFmt(r.created_at) }}
                        </div>
                    </div>
                    <span class="inline-flex rounded-full bg-secondary px-2 py-0.5 text-[10px] text-muted-foreground">{{ r.status }}</span>
                </li>
            </ul>
        </section>

        <!-- Comments -->
        <section v-if="post.comments?.length" class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold">Comments ({{ post.comments.length }})</h3>
            <ul class="divide-y divide-border/60">
                <li v-for="c in post.comments" :key="c.uuid" class="flex items-start gap-3 py-3 text-sm">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold">
                        {{ initials(c.user?.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ c.user?.name ?? 'unknown' }}</span>
                            <span class="text-[11px] text-muted-foreground">{{ dateFmt(c.created_at) }}</span>
                        </div>
                        <p class="mt-0.5">{{ c.body }}</p>
                    </div>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
