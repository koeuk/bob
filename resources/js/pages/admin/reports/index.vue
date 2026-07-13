<template>
    <Head title="Reports" />
    <AdminLayout title="Reports">
        <!-- Tabs -->
        <div class="relative flex items-center rounded-full border-2 border-white dark:border-white/10 shadow-sm bg-card p-1 shadow-sm w-fit">
            <!-- Sliding indicator -->
            <div
                class="absolute rounded-full bg-moss transition-all duration-300 ease-in-out"
                :style="sliderStyle"
            />
            <button
                v-for="(t, i) in tabs"
                :key="t.key"
                :ref="el => { if (el) tabRefs[i] = el }"
                class="relative z-10 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                :class="activeTab === t.key ? 'text-paper' : 'text-muted-foreground hover:text-moss'"
                @click="setTab(t.key)"
            >
                {{ t.label }}
                <Badge
                    :class="[
                        'rounded-full border-0',
                        activeTab === t.key
                            ? 'bg-paper/20 text-paper'
                            : t.tone === 'rust' ? 'bg-rust/15 text-rust'
                            : t.tone === 'moss' ? 'bg-moss/15 text-moss'
                            : 'bg-secondary',
                    ]"
                >{{ t.count }}</Badge>
            </button>
        </div>

        <!-- Queue -->
        <Card class="rounded-3xl border-white gap-0 overflow-hidden">
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
                            <Link :href="`/admin/reports/${r.uuid}`" class="truncate font-medium hover:text-moss">
                                {{ r.reason }}
                            </Link>
                            <Badge :class="['rounded-full border-0', statusTone(r.status)]">{{ r.status }}</Badge>
                            <Badge class="rounded-full border-0 bg-secondary text-muted-foreground">{{ reportableLabel(r) }}</Badge>
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            filed by {{ r.reporter?.name ?? 'unknown' }}
                            <span v-if="r.reviewer"> · reviewed by {{ r.reviewer.name }}</span>
                            · {{ dateFmt(r.created_at) }}
                        </div>
                    </div>
                    <Link :href="`/admin/reports/${r.uuid}`" class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground group-hover:bg-moss group-hover:text-paper">
                        <ChevronRight class="size-4" />
                    </Link>
                </li>
            </ul>

            <div v-else class="py-20 text-center">
                <Inbox class="mx-auto size-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">No reports in this queue.</p>
            </div>

            <div v-if="reports.data.length" class="flex items-center justify-between border-t border-white px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ reports.from }}–{{ reports.to }} of {{ reports.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in reports.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs',
                            link.active ? 'bg-moss text-paper' : link.url ? 'hover:bg-secondary' : 'opacity-40',
                        ]"
                        preserve-scroll
                        preserve-state
                    />
                </div>
            </div>
        </Card>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Flag, Inbox } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    reports: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
});

const activeTab = ref(props.filters?.filter?.status ?? 'all');
const tabRefs = ref([]);
const sliderStyle = ref({});

const updateSlider = () => {
    const activeIndex = tabs.value.findIndex(t => t.key === activeTab.value);
    const el = tabRefs.value[activeIndex];
    if (el) {
        sliderStyle.value = {
            left: `${el.offsetLeft}px`,
            top: `${el.offsetTop}px`,
            width: `${el.offsetWidth}px`,
            height: `${el.offsetHeight}px`,
        };
    }
};

onMounted(() => nextTick(updateSlider));
watch(activeTab, () => nextTick(updateSlider));

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
