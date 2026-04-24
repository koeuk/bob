<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Flag, Inbox } from 'lucide-vue-next';

defineProps({
    reports: { type: Object, required: true },
});

const dateFmt = (iso) => iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const statusTone = (s) => {
    switch (s) {
        case 'pending': return 'bg-rust/15 text-rust';
        case 'resolved': return 'bg-moss/15 text-moss';
        case 'dismissed': return 'bg-secondary text-muted-foreground';
        default: return 'bg-ink/10 text-ink';
    }
};

const reportableLabel = (r) => (r.reportable_type?.split('\\').pop() ?? 'Item');
</script>

<template>
    <Head title="My Reports" />
    <AppLayout>
        <section class="pt-2">
            <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">Reports</h1>
            <p class="mt-1 text-sm text-muted-foreground">Reports you've filed and what happened.</p>
        </section>

        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <ul v-if="reports.data.length" class="divide-y divide-border/60">
                <li
                    v-for="r in reports.data"
                    :key="r.uuid"
                    class="flex items-start gap-4 px-6 py-4"
                >
                    <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-2xl bg-rust/10 text-rust">
                        <Flag class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="truncate font-medium">{{ r.reason }}</span>
                            <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', statusTone(r.status)]">
                                {{ r.status }}
                            </span>
                            <span class="inline-flex rounded-full bg-secondary px-2 py-0.5 text-[10px] text-muted-foreground">
                                {{ reportableLabel(r) }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            Filed {{ dateFmt(r.created_at) }}
                            <span v-if="r.reviewer"> · reviewed by {{ r.reviewer.name }}</span>
                            <span v-if="r.reviewed_at"> on {{ dateFmt(r.reviewed_at) }}</span>
                        </div>
                        <div v-if="r.resolution_note" class="mt-2 rounded-2xl bg-secondary/40 px-3 py-2 text-xs">
                            <span class="font-medium">Moderator note:</span> {{ r.resolution_note }}
                        </div>
                    </div>
                </li>
            </ul>
            <div v-else class="py-16 text-center">
                <Inbox class="mx-auto size-10 text-muted-foreground" />
                <p class="mt-3 text-sm text-muted-foreground">You haven't filed any reports.</p>
            </div>

            <div v-if="reports.data.length && reports.links.length > 3" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
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
    </AppLayout>
</template>
