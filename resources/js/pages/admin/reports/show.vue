<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, Eye, Flag, X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    report: { type: Object, required: true },
});

const page = usePage();

const reportableLabel = computed(() => props.report.reportable_type?.split('\\').pop() ?? 'Item');

const dateFmt = (iso) => iso ? new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const statusTone = (s) => {
    switch (s) {
        case 'pending': return 'bg-rust/15 text-rust';
        case 'resolved': return 'bg-moss/15 text-moss';
        case 'dismissed': return 'bg-secondary text-muted-foreground';
        default: return 'bg-ink/10 text-ink';
    }
};

const resolveForm = useForm({ resolution_note: '' });
const dismissForm = useForm({ resolution_note: '' });

const markReviewed = () => router.post(`/admin/reports/${props.report.uuid}/review`, {}, { preserveScroll: true });
const resolve = () => resolveForm.post(`/admin/reports/${props.report.uuid}/resolve`, { preserveScroll: true });
const dismiss = () => dismissForm.post(`/admin/reports/${props.report.uuid}/dismiss`, { preserveScroll: true });
</script>

<template>
    <Head title="Report" />
    <AdminLayout>
        <Link href="/admin/reports" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to reports
        </Link>

        <section class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-rust/10 text-rust">
                        <Flag class="size-6" />
                    </span>
                    <div>
                        <h2 class="font-sans text-2xl font-semibold tracking-tight">{{ report.reason }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium', statusTone(report.status)]">
                                {{ report.status }}
                            </span>
                            <span class="inline-flex rounded-full bg-secondary px-2.5 py-0.5 text-xs text-muted-foreground">
                                {{ reportableLabel }}
                            </span>
                        </div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            Filed by <span class="font-medium text-ink">{{ report.reporter?.name ?? 'unknown' }}</span>
                            &middot; {{ dateFmt(report.created_at) }}
                        </div>
                    </div>
                </div>

                <div v-if="report.status === 'pending'" class="flex flex-wrap gap-2">
                    <button
                        class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-medium hover:bg-secondary"
                        @click="markReviewed"
                    >
                        <Eye class="size-4" /> Mark reviewed
                    </button>
                </div>
            </div>
        </section>

        <!-- Reported content preview -->
        <section class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <div class="mb-3 text-xs uppercase tracking-wide text-muted-foreground">Reported {{ reportableLabel.toLowerCase() }}</div>
            <div v-if="report.reportable" class="rounded-2xl bg-secondary/40 p-4 text-sm">
                <pre class="whitespace-pre-wrap break-words font-sans">{{ report.reportable.body ?? report.reportable.name ?? JSON.stringify(report.reportable, null, 2) }}</pre>
            </div>
            <div v-else class="rounded-2xl bg-secondary/40 p-4 text-sm text-muted-foreground">
                The reported item no longer exists.
            </div>
        </section>

        <!-- Review form -->
        <section v-if="report.status !== 'resolved' && report.status !== 'dismissed'" class="grid gap-4 lg:grid-cols-2">
            <form class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm" @submit.prevent="resolve">
                <div class="mb-3 flex items-center gap-2">
                    <Check class="size-4 text-moss" />
                    <h3 class="text-lg font-semibold">Resolve</h3>
                </div>
                <p class="mb-4 text-xs text-muted-foreground">Mark this report as resolved — action was taken.</p>
                <textarea
                    v-model="resolveForm.resolution_note"
                    rows="3"
                    class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                    placeholder="Deleted offending post, warned user..."
                    required
                />
                <p v-if="resolveForm.errors.resolution_note" class="mt-1 text-xs text-destructive">{{ resolveForm.errors.resolution_note }}</p>
                <button type="submit" class="mt-3 inline-flex items-center gap-2 rounded-full bg-moss px-4 py-2 text-sm font-medium text-paper hover:opacity-90" :disabled="resolveForm.processing">
                    <Check class="size-4" /> Resolve report
                </button>
            </form>

            <form class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm" @submit.prevent="dismiss">
                <div class="mb-3 flex items-center gap-2">
                    <X class="size-4 text-muted-foreground" />
                    <h3 class="text-lg font-semibold">Dismiss</h3>
                </div>
                <p class="mb-4 text-xs text-muted-foreground">No action needed. Note is optional.</p>
                <textarea
                    v-model="dismissForm.resolution_note"
                    rows="3"
                    class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                    placeholder="Duplicate report..."
                />
                <button type="submit" class="mt-3 inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-medium hover:bg-secondary" :disabled="dismissForm.processing">
                    <X class="size-4" /> Dismiss
                </button>
            </form>
        </section>

        <section v-else class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-muted-foreground">Resolution</div>
            <div class="mt-2 text-sm">{{ report.resolution_note || '—' }}</div>
            <div class="mt-2 text-xs text-muted-foreground">
                {{ report.status }} by {{ report.reviewer?.name ?? 'system' }} &middot; {{ dateFmt(report.reviewed_at) }}
            </div>
        </section>
    </AdminLayout>
</template>
