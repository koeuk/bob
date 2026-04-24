<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Flag, Heart, MessageCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters?.filter?.search ?? '');
const status = ref(props.filters?.filter?.status ?? '');

const apply = () => router.get('/admin/posts', {
    filter: {
        ...(search.value ? { search: search.value } : {}),
        ...(status.value ? { status: status.value } : {}),
    },
}, { preserveState: true, preserveScroll: true, replace: true });

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const statusTone = (s) => {
    switch (s) {
        case 'flagged': return 'bg-rust/15 text-rust';
        case 'hidden': return 'bg-ink/10 text-ink';
        default: return 'bg-moss/15 text-moss';
    }
};

const truncate = (t, n = 160) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
</script>

<template>
    <Head title="Posts" />
    <AdminLayout title="Posts">
        <div class="flex flex-wrap items-center gap-3 rounded-3xl border border-border/60 bg-card p-4 shadow-sm">
            <div class="relative min-w-0 flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search body..."
                    class="h-10 w-full rounded-full bg-secondary/60 pl-10 pr-4 text-sm outline-none focus:bg-secondary"
                    @keydown.enter="apply"
                />
            </div>
            <select v-model="status" class="h-10 rounded-full bg-secondary/60 px-4 text-sm outline-none" @change="apply">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="flagged">Flagged</option>
                <option value="hidden">Hidden</option>
            </select>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="p in posts.data"
                :key="p.uuid"
                :href="`/admin/posts/${p.uuid}`"
                class="group flex flex-col gap-3 rounded-3xl border border-border/60 bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-full bg-ink text-[11px] font-semibold text-paper">
                            {{ initials(p.user?.name) }}
                        </span>
                        <div>
                            <div class="text-sm font-medium">{{ p.user?.name ?? 'unknown' }}</div>
                            <div class="text-[11px] text-muted-foreground">{{ dateFmt(p.created_at) }}</div>
                        </div>
                    </div>
                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', statusTone(p.status)]">
                        {{ p.status }}
                    </span>
                </div>
                <p class="text-sm leading-relaxed text-muted-foreground group-hover:text-ink">
                    {{ truncate(p.body) }}
                </p>
                <div class="flex items-center gap-5 pt-1 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1"><Heart class="size-3.5" /> {{ p.likes_count }}</span>
                    <span class="inline-flex items-center gap-1"><MessageCircle class="size-3.5" /> {{ p.comments_count }}</span>
                    <span v-if="p.reports_count > 0" class="inline-flex items-center gap-1 text-rust">
                        <Flag class="size-3.5" /> {{ p.reports_count }}
                    </span>
                </div>
            </Link>
        </div>

        <div v-if="!posts.data.length" class="rounded-3xl border border-border/60 bg-card py-16 text-center text-sm text-muted-foreground shadow-sm">
            No posts match.
        </div>

        <div v-if="posts.data.length" class="flex items-center justify-between text-xs text-muted-foreground">
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
    </AdminLayout>
</template>
