<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    pages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const deletePage = (p) => {
    if (!window.confirm(`Delete page "${p.title}"?`)) return;
    router.delete(`/admin/pages/${p.uuid}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Pages" />
    <AdminLayout title="Pages">
        <div v-if="page.props.flash?.status" class="rounded-2xl bg-moss/10 px-4 py-3 text-sm text-moss">
            {{ page.props.flash.status }}
        </div>

        <div class="flex justify-end">
            <Link
                href="/admin/pages/create"
                class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
            >
                <Plus class="size-4" /> New page
            </Link>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div
                v-for="p in pages.data"
                :key="p.uuid"
                class="group flex flex-col gap-3 rounded-3xl border border-border/60 bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-2xl bg-secondary text-ink">
                            <FileText class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <Link :href="`/admin/pages/${p.uuid}/edit`" class="truncate font-medium hover:text-rust">
                                {{ p.title }}
                            </Link>
                            <div class="truncate text-xs text-muted-foreground">/{{ p.slug }}</div>
                        </div>
                    </div>
                    <span
                        :class="[
                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium',
                            p.status === 'published' ? 'bg-moss/15 text-moss' : 'bg-secondary text-muted-foreground',
                        ]"
                    >{{ p.status }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Updated {{ dateFmt(p.updated_at) }} by {{ p.updated_by?.name ?? 'system' }}</span>
                    <button
                        class="inline-flex size-7 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        @click="deletePage(p)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!pages.data.length" class="rounded-3xl border border-border/60 bg-card py-16 text-center text-sm text-muted-foreground shadow-sm">
            No pages yet. Create one to get started.
        </div>
    </AdminLayout>
</template>
