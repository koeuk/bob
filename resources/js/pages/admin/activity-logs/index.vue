<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ActivitySquare, Hash, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const actionFilter = ref(props.filters?.filter?.action ?? '');

const apply = () => router.get('/admin/activity-logs', actionFilter.value ? { filter: { action: actionFilter.value } } : {}, {
    preserveState: true, preserveScroll: true, replace: true,
});

const dateFmt = (iso) => new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const actionTone = (action) => {
    if (action.includes('delete')) return 'bg-rust/15 text-rust';
    if (action.includes('ban')) return 'bg-rust/15 text-rust';
    if (action.includes('unban') || action.includes('resolve')) return 'bg-moss/15 text-moss';
    if (action.includes('update') || action.includes('flag')) return 'bg-ink/10 text-ink';
    return 'bg-secondary text-muted-foreground';
};
</script>

<template>
    <Head title="Activity" />
    <AdminLayout title="Activity log">
        <div class="flex flex-wrap items-center gap-3 rounded-3xl border border-border/60 bg-card p-4 shadow-sm">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="actionFilter"
                    type="search"
                    placeholder="Filter by action (e.g. user.ban, post.delete)..."
                    class="h-10 w-full rounded-full bg-secondary/60 pl-10 pr-4 text-sm outline-none focus:bg-secondary"
                    @keydown.enter="apply"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <ul v-if="logs.data.length" class="divide-y divide-border/60">
                <li v-for="log in logs.data" :key="log.uuid" class="flex items-start gap-4 px-6 py-3">
                    <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-[11px] font-semibold text-paper">
                        {{ initials(log.admin?.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium">{{ log.admin?.name ?? 'system' }}</span>
                            <span :class="['inline-flex rounded-full px-2 py-0.5 font-mono text-[10px]', actionTone(log.action)]">
                                {{ log.action }}
                            </span>
                        </div>
                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                            <span class="inline-flex items-center gap-1">
                                <Hash class="size-3" /> {{ log.target_type ? log.target_type.split('\\').pop() : 'n/a' }}
                            </span>
                            <span v-if="log.ip">IP {{ log.ip }}</span>
                        </div>
                    </div>
                    <span class="shrink-0 text-[11px] text-muted-foreground">{{ dateFmt(log.created_at) }}</span>
                </li>
            </ul>
            <div v-else class="py-16 text-center">
                <ActivitySquare class="mx-auto size-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No activity recorded yet.</p>
            </div>

            <div v-if="logs.data.length" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in logs.links"
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
        </div>
    </AdminLayout>
</template>
