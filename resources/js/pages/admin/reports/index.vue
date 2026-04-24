<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, Flag, Inbox } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    reports: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
});

const page = usePage();
const activeTab = ref(props.filters?.filter?.status ?? 'all');

const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.reports.total },
    { key: 'pending', label: 'Pending', count: props.counts.pending, tone: 'rust' },
    { key: 'reviewed', label: 'Reviewed', count: props.counts.reviewed },
    { key: 'resolved', label: 'Resolved', count: props.counts.resolved, tone: 'moss' },
    { key: 'dismissed', label: 'Dismissed', count: props.counts.dismissed },
]);

const setTab = (key) => {
    activeTab.value = key;
    router.get('/admin/reports', key === 'all' ? {} : { filter: { status: key } }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
};

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const reportableLabel = (r) => (r.reportable_type?.split('\\').pop() ?? 'Item');
const statusTone = (s) => {
    switch (s) {
        case 'pending': return 'bg-rust/15 text-rust';
        case 'resolved': return 'bg-moss/15 text-moss';
        case 'dismissed': return 'bg-secondary text-muted-foreground';
        default: return 'bg-ink/10 text-ink';
    }
};
</script>

<template>
    <Head title="Reports" />
    <AdminLayout title="Reports">
        <div v-if="page.props.flash?.status" class="rounded-2xl bg-moss/10 px-4 py-3 text-sm text-moss">
            {{ page.props.flash.status }}
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap items-center gap-2 rounded-full border border-border/60 bg-card p-1.5 shadow-sm w-fit">
            <button
                v-for="t in tabs"
                :key="t.key"
                :class="[
                    'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                    activeTab === t.key ? 'bg-ink text-paper' : 'text-muted-foreground hover:text-ink',
                ]"
                @click="setTab(t.key)"
            >
                {{ t.label }}
                <span
                    :class="[
                        'inline-flex rounded-full px-2 py-0.5 text-[10px]',
                        activeTab === t.key
                            ? 'bg-paper/20 text-paper'
                            : t.tone === 'rust' ? 'bg-rust/15 text-rust'
                            : t.tone === 'moss' ? 'bg-moss/15 text-moss'
                            : 'bg-secondary',
                    ]"
                >{{ t.count }}</span>
            </button>
        </div>

        <!-- Queue -->
        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <ul v-if="reports.data.length" class="divide-y divide-border/60">
                <li
                    v-for="r in reports.data"
                    :key="r.uuid"
                    class="group flex items-start gap-4 px-6 py-4 transition-colors hover:bg-secondary/40"
                >
                    <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-2xl bg-rust/10 text-rust">
                        <Flag class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="`/admin/reports/${r.uuid}`" class="truncate font-medium hover:text-rust">
                                {{ r.reason }}
                            </Link>
                            <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', statusTone(r.status)]">
                                {{ r.status }}
                            </span>
                            <span class="inline-flex rounded-full bg-secondary px-2 py-0.5 text-[10px] text-muted-foreground">
                                {{ reportableLabel(r) }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            filed by {{ r.reporter?.name ?? 'unknown' }}
                            <span v-if="r.reviewer"> · reviewed by {{ r.reviewer.name }}</span>
                            · {{ dateFmt(r.created_at) }}
                        </div>
                    </div>
                    <Link :href="`/admin/reports/${r.uuid}`" class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground group-hover:bg-ink group-hover:text-paper">
                        <ChevronRight class="size-4" />
                    </Link>
                </li>
            </ul>

            <div v-else class="py-20 text-center">
                <Inbox class="mx-auto size-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No reports in this queue.</p>
            </div>

            <div v-if="reports.data.length" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ reports.from }}–{{ reports.to }} of {{ reports.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in reports.links"
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
