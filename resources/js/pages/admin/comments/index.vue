<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Flag, Heart, Pencil, Search, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    comments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters?.filter?.search ?? '');

const apply = () => router.get('/admin/comments', search.value ? { filter: { search: search.value } } : {}, {
    preserveState: true, preserveScroll: true, replace: true,
});

const deleteComment = (c) => {
    if (!window.confirm('Delete this comment?')) return;
    router.delete(`/admin/comments/${c.uuid}`, { preserveScroll: true });
};

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const truncate = (t, n = 80) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;

// Edit modal
const editTarget = ref(null);
const editForm = useForm({ body: '', reason: '' });
const openEdit = (c) => {
    editForm.body = c.body;
    editForm.reason = '';
    editTarget.value = c;
};
const submitEdit = () => editForm.patch(`/admin/comments/${editTarget.value.uuid}`, {
    preserveScroll: true,
    onSuccess: () => { editTarget.value = null; editForm.reset(); },
});
</script>

<template>
    <Head title="Comments" />
    <AdminLayout title="Comments">
        <div class="flex flex-wrap items-center gap-3 rounded-3xl border border-border/60 bg-card p-4 shadow-sm">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search comment body..."
                    class="h-10 w-full rounded-full bg-secondary/60 pl-10 pr-4 text-sm outline-none focus:bg-secondary"
                    @keydown.enter="apply"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <ul v-if="comments.data.length" class="divide-y divide-border/60">
                <li v-for="c in comments.data" :key="c.uuid" class="flex items-start gap-4 px-6 py-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold">
                        {{ initials(c.user?.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium">{{ c.user?.name ?? 'unknown' }}</span>
                            <span class="text-[11px] text-muted-foreground">{{ dateFmt(c.created_at) }}</span>
                            <Link v-if="c.post" :href="`/admin/posts/${c.post.uuid}`" class="text-[11px] text-muted-foreground hover:text-rust">
                                on &ldquo;{{ truncate(c.post.body, 40) }}&rdquo;
                            </Link>
                        </div>
                        <p class="mt-1 text-sm">{{ c.body }}</p>
                        <div class="mt-1.5 flex items-center gap-3 text-[11px] text-muted-foreground">
                            <span class="inline-flex items-center gap-1"><Heart class="size-3" /> {{ c.likes_count }}</span>
                            <span v-if="c.reports_count > 0" class="inline-flex items-center gap-1 text-rust">
                                <Flag class="size-3" /> {{ c.reports_count }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-ink"
                            title="Edit"
                            @click="openEdit(c)"
                        >
                            <Pencil class="size-4" />
                        </button>
                        <button
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                            title="Delete"
                            @click="deleteComment(c)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </li>
            </ul>
            <div v-else class="py-16 text-center text-sm text-muted-foreground">
                No comments.
            </div>

            <div v-if="comments.data.length" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ comments.from }}–{{ comments.to }} of {{ comments.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in comments.links"
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

        <!-- Edit modal -->
        <Teleport to="body">
            <div v-if="editTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">Edit comment</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        by {{ editTarget.user?.name ?? 'unknown' }} · {{ dateFmt(editTarget.created_at) }}
                    </p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitEdit">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body</label>
                            <textarea
                                v-model="editForm.body"
                                rows="5"
                                class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                                required
                            />
                            <p v-if="editForm.errors.body" class="mt-1 text-xs text-destructive">{{ editForm.errors.body }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Moderation reason (optional)</label>
                            <input
                                v-model="editForm.reason"
                                type="text"
                                class="h-10 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                                placeholder="Logged in activity log"
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="rounded-full px-4 py-2 text-sm hover:bg-secondary" @click="editTarget = null">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90" :disabled="editForm.processing">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
