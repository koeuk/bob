<template>
    <Head title="Likes" />

    <Dialog :open="!!removeTarget" @update:open="(v) => { if (!v) removeTarget = null }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Remove reaction?</DialogTitle>
                <DialogDescription>
                    This will permanently remove this reaction. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="removeTarget = null">
                    Cancel
                </Button>
                <Button class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmRemove">
                    <Trash2 class="size-4" /> Remove
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <AdminLayout title="Likes & Reactions">
        <div class="flex gap-3">
            <Card class="rounded-3xl border-white gap-0 px-8 py-4 w-40">
                <div class="text-[11px] uppercase tracking-wide text-muted-foreground">Total</div>
                <div class="font-sans text-2xl font-semibold">{{ counts.all }}</div>
            </Card>
            <Card class="rounded-3xl border-white gap-0 bg-rust/5 px-8 py-4 w-40">
                <div class="text-[11px] uppercase tracking-wide text-rust">Today</div>
                <div class="font-sans text-2xl font-semibold text-rust">{{ counts.today }}</div>
            </Card>
        </div>

        <div class="overflow-hidden rounded-3xl border-2 border-white dark:border-white/10 shadow-sm bg-card">
            <div class="grid grid-cols-[2rem_1.2fr_0.8fr_1fr_2fr_8rem_2.5rem] items-center gap-3 border-b border-white px-6 py-3 text-[11px] uppercase tracking-wide text-muted-foreground">
                <span></span>
                <span>User</span>
                <span>Type</span>
                <span>Target</span>
                <span>Preview</span>
                <span>When</span>
                <span></span>
            </div>

            <ul v-if="likes.data.length" class="divide-y divide-border/60">
                <li
                    v-for="l in likes.data"
                    :key="l.uuid"
                    class="grid grid-cols-[2rem_1.2fr_0.8fr_1fr_2fr_8rem_2.5rem] items-center gap-3 px-6 py-3 text-sm"
                >
                    <span class="inline-flex size-7 items-center justify-center rounded-full bg-rust/10 text-rust">
                        <Heart class="size-3.5" />
                    </span>
                    <Link
                        v-if="l.user"
                        :href="`/admin/users/${l.user.uuid}`"
                        class="truncate font-medium hover:text-moss"
                    >{{ l.user.name }}</Link>
                    <span v-else class="truncate text-muted-foreground">deleted user</span>
                    <span>
                        <Badge class="rounded-full border-0 text-[10px] font-medium" :class="typeTone(l.type)">
                            {{ l.type }}
                        </Badge>
                    </span>
                    <Badge class="rounded-full border-0 bg-secondary text-[10px] text-muted-foreground w-fit">
                        {{ targetLabel(l) }}
                    </Badge>
                    <span class="truncate text-muted-foreground">{{ truncate(targetPreview(l)) }}</span>
                    <span class="text-[11px] text-muted-foreground">{{ dateFmt(l.created_at) }}</span>
                    <button
                        class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        title="Remove"
                        @click="removeLike(l)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </li>
            </ul>
            <div v-else class="py-16 text-center text-sm text-muted-foreground">
                No reactions yet.
            </div>

            <div v-if="likes.data.length && likes.links.length > 3" class="flex items-center justify-between border-t border-white px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ likes.from }}–{{ likes.to }} of {{ likes.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in likes.links"
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
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Heart, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/Badge.vue';

const props = defineProps({
    likes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
});

const page = usePage();

const dateFmt = (iso) => iso ? new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
const targetLabel = (l) => (l.likeable_type?.split('\\').pop() ?? 'Item');
const targetPreview = (l) => {
    if (!l.likeable) return '—';
    return l.likeable.body ?? l.likeable.name ?? l.likeable.title ?? '—';
};
const truncate = (t, n = 80) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;

const typeTone = (t) => {
    switch (t) {
        case 'love': return 'bg-rust/15 text-rust';
        case 'bookmark': return 'bg-ink/10 text-ink';
        case 'haha':
        case 'wow': return 'bg-moss/15 text-moss';
        case 'sad':
        case 'angry': return 'bg-rust/10 text-rust';
        default: return 'bg-secondary text-muted-foreground';
    }
};

const removeTarget = ref(null);
const confirmRemove = () => {
    if (!removeTarget.value) return;
    router.delete(`/admin/likes/${removeTarget.value.uuid}`, { preserveScroll: true });
    removeTarget.value = null;
};
const removeLike = (like) => { removeTarget.value = like; };
</script>
