<template>
    <Head title="Pages" />

    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Delete page?</DialogTitle>
                <DialogDescription>
                    This will permanently delete <strong>{{ deleteTarget?.title }}</strong>. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="deleteTarget = null">
                    Cancel
                </Button>
                <Button class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDelete">
                    <Trash2 class="size-4" /> Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <AdminLayout title="Pages">
        <div class="flex justify-end">
            <Button as-child class="rounded-full bg-moss text-paper hover:bg-moss/90">
                <Link href="/admin/pages/create">
                    <Plus class="size-4" /> New page
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 stagger-rows">
            <Card
                v-for="p in pages.data"
                :key="p.uuid"
                class="glass rounded-3xl gap-0 group flex flex-col gap-3 p-5 card-hover"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-2xl bg-secondary text-ink">
                            <FileText class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <Link :href="`/admin/pages/${p.uuid}/edit`" class="truncate font-medium hover:text-moss">
                                {{ p.title }}
                            </Link>
                            <div class="truncate text-xs text-muted-foreground">/{{ p.slug }}</div>
                        </div>
                    </div>
                    <Badge
                        class="rounded-full border-0 shrink-0 text-[10px] font-medium"
                        :class="p.status === 'published' ? 'bg-moss/15 text-moss' : 'bg-secondary text-muted-foreground'"
                    >{{ p.status }}</Badge>
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
            </Card>
        </div>

        <div v-if="!pages.data.length" class="glass rounded-3xl py-16 text-center text-sm text-muted-foreground">
            No pages yet. Create one to get started.
        </div>

        <!-- The list is paginated at 25; without these links later pages were unreachable. -->
        <div v-if="pages.data.length" class="flex items-center justify-between text-xs text-muted-foreground">
            <span>Showing {{ pages.from }}–{{ pages.to }} of {{ pages.total }}</span>
            <div class="flex items-center gap-1">
                <Link
                    v-for="link in pages.links"
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
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/Badge.vue';

const props = defineProps({
    pages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const deleteTarget = ref(null);
const confirmDelete = () => {
    if (!deleteTarget.value) return;
    router.delete(`/admin/pages/${deleteTarget.value.uuid}`, { preserveScroll: true });
    deleteTarget.value = null;
};
const deletePage = (p) => { deleteTarget.value = p; };
</script>
