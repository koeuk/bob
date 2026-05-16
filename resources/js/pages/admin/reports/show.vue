<template>
    <Head title="Report" />
    <AdminLayout>
        <Link href="/admin/reports" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to reports
        </Link>

        <Card class="rounded-3xl border-border/60 gap-0 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-rust/10 text-rust">
                        <Flag class="size-6" />
                    </span>
                    <div>
                        <h2 class="font-sans text-2xl font-semibold tracking-tight">{{ report.reason }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <Badge :class="['rounded-full border-0', statusTone(report.status)]">{{ report.status }}</Badge>
                            <Badge class="rounded-full border-0 bg-secondary text-muted-foreground">{{ reportableLabel }}</Badge>
                        </div>
                        <div class="mt-2 text-sm text-muted-foreground">
                            Filed by <span class="font-medium text-ink">{{ report.reporter?.name ?? 'unknown' }}</span>
                            &middot; {{ dateFmt(report.created_at) }}
                        </div>
                    </div>
                </div>

                <div v-if="report.status === 'pending'" class="flex flex-wrap gap-2">
                    <Button variant="outline" class="rounded-full" @click="markReviewed">
                        <Eye class="size-4" /> Mark reviewed
                    </Button>
                </div>
            </div>
        </Card>

        <!-- Reported content preview -->
        <Card class="rounded-3xl border-border/60 gap-0 p-6">
            <div class="mb-3 text-xs uppercase tracking-wide text-muted-foreground">Reported {{ reportableLabel.toLowerCase() }}</div>
            <div v-if="report.reportable" class="rounded-2xl bg-secondary/40 p-4 text-sm">
                <pre class="whitespace-pre-wrap break-words font-sans">{{ report.reportable.body ?? report.reportable.name ?? JSON.stringify(report.reportable, null, 2) }}</pre>
            </div>
            <div v-else class="rounded-2xl bg-secondary/40 p-4 text-sm text-muted-foreground">
                The reported item no longer exists.
            </div>
        </Card>

        <!-- Review form -->
        <section v-if="report.status !== 'resolved' && report.status !== 'dismissed'" class="grid gap-4 lg:grid-cols-2">
            <form @submit.prevent="resolve">
                <Card class="rounded-3xl border-border/60 gap-0 p-6">
                    <div class="mb-3 flex items-center gap-2">
                        <Check class="size-4 text-moss" />
                        <h3 class="text-lg font-semibold">Resolve</h3>
                    </div>
                    <p class="mb-4 text-xs text-muted-foreground">Mark this report as resolved — action was taken.</p>
                    <Textarea
                        v-model="resolveForm.resolution_note"
                        rows="3"
                        class="rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
                        placeholder="Deleted offending post, warned user..."
                        required
                    />
                    <p v-if="resolveForm.errors.resolution_note" class="mt-1 text-xs text-destructive">{{ resolveForm.errors.resolution_note }}</p>
                    <Button type="submit" class="mt-3 rounded-full bg-moss text-paper hover:bg-moss/90" :disabled="resolveForm.processing">
                        <Check class="size-4" /> Resolve report
                    </Button>
                </Card>
            </form>

            <form @submit.prevent="dismiss">
                <Card class="rounded-3xl border-border/60 gap-0 p-6">
                    <div class="mb-3 flex items-center gap-2">
                        <X class="size-4 text-muted-foreground" />
                        <h3 class="text-lg font-semibold">Dismiss</h3>
                    </div>
                    <p class="mb-4 text-xs text-muted-foreground">No action needed. Note is optional.</p>
                    <Textarea
                        v-model="dismissForm.resolution_note"
                        rows="3"
                        class="rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
                        placeholder="Duplicate report..."
                    />
                    <Button type="submit" variant="outline" class="mt-3 rounded-full" :disabled="dismissForm.processing">
                        <X class="size-4" /> Dismiss
                    </Button>
                </Card>
            </form>
        </section>

        <Card v-else class="rounded-3xl border-border/60 gap-0 p-6">
            <div class="text-xs uppercase tracking-wide text-muted-foreground">Resolution</div>
            <div class="mt-2 text-sm">{{ report.resolution_note || '—' }}</div>
            <div class="mt-2 text-xs text-muted-foreground">
                {{ report.status }} by {{ report.reviewer?.name ?? 'system' }} &middot; {{ dateFmt(report.reviewed_at) }}
            </div>
        </Card>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/Badge.vue';
import Textarea from '@/components/ui/Textarea.vue';
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
