<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Heart, MessageCircle, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
});

const page = usePage();

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const statusTone = (s) => {
    switch (s) {
        case 'flagged': return 'bg-rust/15 text-rust';
        case 'hidden': return 'bg-ink/10 text-ink';
        default: return 'bg-moss/15 text-moss';
    }
};
const truncate = (t, n = 200) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;

const deletePost = (p) => {
    if (!window.confirm('Delete this post?')) return;
    router.delete(`/posts/${p.uuid}`, { preserveScroll: true });
};

const totals = computed(() => ({
    total: props.posts.total,
    active: props.posts.data.filter((p) => p.status === 'active').length,
    flagged: props.posts.data.filter((p) => p.status === 'flagged').length,
}));
</script>

<template>
    <Head title="My Posts" />
    <AppLayout>
        <section class="flex items-end justify-between gap-4 pt-2">
            <div>
                <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">My posts</h1>
                <p class="mt-1 text-sm text-muted-foreground">Everything you've written.</p>
            </div>
            <Link
                href="/feed"
                class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
            >
                <Pencil class="size-4" /> Write new
            </Link>
        </section>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-3xl border border-border/60 bg-card px-5 py-4 shadow-sm">
                <div class="text-[11px] uppercase tracking-wide text-muted-foreground">Total</div>
                <div class="font-sans text-2xl font-semibold">{{ totals.total }}</div>
            </div>
            <div class="rounded-3xl border border-border/60 bg-card px-5 py-4 shadow-sm">
                <div class="text-[11px] uppercase tracking-wide text-moss">Active (this page)</div>
                <div class="font-sans text-2xl font-semibold text-moss">{{ totals.active }}</div>
            </div>
            <div class="rounded-3xl border border-border/60 bg-card px-5 py-4 shadow-sm">
                <div class="text-[11px] uppercase tracking-wide text-rust">Flagged</div>
                <div class="font-sans text-2xl font-semibold text-rust">{{ totals.flagged }}</div>
            </div>
        </div>

        <!-- List -->
        <div v-if="posts.data.length" class="space-y-3">
            <article
                v-for="p in posts.data"
                :key="p.uuid"
                class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm"
            >
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                        <span>{{ dateFmt(p.created_at) }}</span>
                        <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', statusTone(p.status)]">
                            {{ p.status }}
                        </span>
                    </div>
                    <button
                        class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        title="Delete"
                        @click="deletePost(p)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </header>

                <Link :href="`/posts/${p.uuid}`" class="mt-2 block">
                    <p class="whitespace-pre-wrap text-[15px] leading-relaxed hover:text-rust">{{ truncate(p.body) }}</p>
                </Link>

                <footer class="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1"><Heart class="size-3.5" /> {{ p.likes_count }}</span>
                    <span class="inline-flex items-center gap-1"><MessageCircle class="size-3.5" /> {{ p.comments_count }}</span>
                </footer>
            </article>
        </div>

        <div v-else class="rounded-3xl border border-border/60 bg-card py-16 text-center shadow-sm">
            <p class="text-sm text-muted-foreground">You haven't posted yet.</p>
            <Link
                href="/feed"
                class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
            >
                <Pencil class="size-4" /> Write your first
            </Link>
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
    </AppLayout>
</template>
