<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, Flag, Heart, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    comments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    posts: { type: Array, default: () => [] },
    authors: { type: Array, default: () => [] },
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

// Create modal
const me = computed(() => page.props.auth?.user);
const showCreate = ref(false);
const createForm = useForm({ post_uuid: '', user_uuid: '', body: '' });

const postSearch = ref('');
const postPickerOpen = ref(false);
const selectedPost = computed(() => props.posts.find((p) => p.uuid === createForm.post_uuid));
const filteredPosts = computed(() => {
    const q = postSearch.value.trim().toLowerCase();
    if (!q) return props.posts.slice(0, 50);
    return props.posts.filter((p) => p.preview.toLowerCase().includes(q)).slice(0, 50);
});
const pickPost = (p) => { createForm.post_uuid = p.uuid; postSearch.value = ''; postPickerOpen.value = false; };
const clearPost = () => { createForm.post_uuid = ''; postSearch.value = ''; };

const authorSearch = ref('');
const authorPickerOpen = ref(false);
const selectedAuthor = computed(() => props.authors.find((a) => a.uuid === createForm.user_uuid));
const filteredAuthors = computed(() => {
    const q = authorSearch.value.trim().toLowerCase();
    if (!q) return props.authors.slice(0, 50);
    return props.authors
        .filter((a) => a.name.toLowerCase().includes(q) || a.email.toLowerCase().includes(q))
        .slice(0, 50);
});
const pickAuthor = (a) => { createForm.user_uuid = a.uuid; authorSearch.value = ''; authorPickerOpen.value = false; };
const clearAuthor = () => { createForm.user_uuid = ''; authorSearch.value = ''; };

const openCreate = () => {
    createForm.reset();
    createForm.user_uuid = me.value?.uuid ?? '';
    showCreate.value = true;
};
const submitCreate = () => createForm.post('/admin/comments', {
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; createForm.reset(); postSearch.value = ''; authorSearch.value = ''; },
});

const roleTone = (role) => {
    switch (role) {
        case 'super_admin': return 'bg-rust/15 text-rust';
        case 'admin': return 'bg-ink/10 text-ink';
        case 'moderator': return 'bg-moss/15 text-moss';
        default: return 'bg-secondary text-muted-foreground';
    }
};
</script>

<template>
    <Head title="Comments" />
    <AdminLayout title="Comments">
        <div class="flex justify-end">
            <button
                class="inline-flex items-center gap-2 rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
                @click="openCreate"
            >
                <Plus class="size-4" /> New comment
            </button>
        </div>

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

        <!-- Create modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">New comment</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Post a comment on behalf of any user.</p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitCreate">
                        <!-- Post picker -->
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Post</label>
                            <div v-if="selectedPost" class="flex items-center justify-between gap-3 rounded-2xl bg-secondary p-3">
                                <p class="min-w-0 flex-1 truncate text-sm">{{ selectedPost.preview }}</p>
                                <button type="button" class="text-xs text-muted-foreground hover:text-ink" @click="clearPost">Change</button>
                            </div>
                            <div v-else class="relative">
                                <button
                                    type="button"
                                    class="flex h-11 w-full items-center justify-between rounded-full bg-secondary/60 px-4 text-sm hover:bg-secondary"
                                    @click="postPickerOpen = !postPickerOpen"
                                >
                                    <span class="text-muted-foreground">Select a post…</span>
                                    <ChevronDown class="size-4 text-muted-foreground" />
                                </button>
                                <div
                                    v-if="postPickerOpen"
                                    class="absolute left-0 right-0 z-10 mt-1 overflow-hidden rounded-2xl border border-border/60 bg-popover shadow-lg"
                                >
                                    <div class="relative border-b border-border/60">
                                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <input
                                            v-model="postSearch"
                                            type="search"
                                            placeholder="Search post body..."
                                            class="h-10 w-full bg-transparent pl-9 pr-3 text-sm outline-none"
                                            autofocus
                                        />
                                    </div>
                                    <ul class="max-h-64 overflow-y-auto">
                                        <li
                                            v-for="p in filteredPosts"
                                            :key="p.uuid"
                                            class="cursor-pointer px-3 py-2 text-sm hover:bg-secondary"
                                            @click="pickPost(p)"
                                        >
                                            {{ p.preview }}
                                        </li>
                                        <li v-if="!filteredPosts.length" class="px-3 py-6 text-center text-xs text-muted-foreground">
                                            No matches.
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p v-if="createForm.errors.post_uuid" class="mt-1 text-xs text-destructive">{{ createForm.errors.post_uuid }}</p>
                        </div>

                        <!-- Author picker -->
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Author</label>
                            <div v-if="selectedAuthor" class="flex items-center justify-between rounded-2xl bg-secondary p-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-ink text-xs font-semibold text-paper">
                                        {{ initials(selectedAuthor.name) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 text-sm font-medium">
                                            {{ selectedAuthor.name }}
                                            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-medium', roleTone(selectedAuthor.role)]">
                                                {{ selectedAuthor.role }}
                                            </span>
                                        </div>
                                        <div class="truncate text-[11px] text-muted-foreground">{{ selectedAuthor.email }}</div>
                                    </div>
                                </div>
                                <button type="button" class="text-xs text-muted-foreground hover:text-ink" @click="clearAuthor">Change</button>
                            </div>
                            <div v-else class="relative">
                                <button
                                    type="button"
                                    class="flex h-11 w-full items-center justify-between rounded-full bg-secondary/60 px-4 text-sm hover:bg-secondary"
                                    @click="authorPickerOpen = !authorPickerOpen"
                                >
                                    <span class="text-muted-foreground">Select an author…</span>
                                    <ChevronDown class="size-4 text-muted-foreground" />
                                </button>
                                <div
                                    v-if="authorPickerOpen"
                                    class="absolute left-0 right-0 z-10 mt-1 overflow-hidden rounded-2xl border border-border/60 bg-popover shadow-lg"
                                >
                                    <div class="relative border-b border-border/60">
                                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <input
                                            v-model="authorSearch"
                                            type="search"
                                            placeholder="Search name or email..."
                                            class="h-10 w-full bg-transparent pl-9 pr-3 text-sm outline-none"
                                            autofocus
                                        />
                                    </div>
                                    <ul class="max-h-64 overflow-y-auto">
                                        <li
                                            v-for="a in filteredAuthors"
                                            :key="a.uuid"
                                            class="flex cursor-pointer items-center gap-3 px-3 py-2 hover:bg-secondary"
                                            @click="pickAuthor(a)"
                                        >
                                            <span class="flex size-8 items-center justify-center rounded-full bg-ink text-[10px] font-semibold text-paper">
                                                {{ initials(a.name) }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-medium">{{ a.name }}</div>
                                                <div class="truncate text-[11px] text-muted-foreground">{{ a.email }}</div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p v-if="createForm.errors.user_uuid" class="mt-1 text-xs text-destructive">{{ createForm.errors.user_uuid }}</p>
                        </div>

                        <!-- Body -->
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body</label>
                            <textarea
                                v-model="createForm.body"
                                rows="5"
                                class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                                placeholder="Write the comment..."
                                required
                                maxlength="2000"
                            />
                            <p class="mt-1 text-[11px] text-muted-foreground">{{ createForm.body.length }}/2000</p>
                            <p v-if="createForm.errors.body" class="mt-1 text-xs text-destructive">{{ createForm.errors.body }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="rounded-full px-4 py-2 text-sm hover:bg-secondary" @click="showCreate = false">Cancel</button>
                            <button
                                type="submit"
                                class="rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                                :disabled="createForm.processing || !createForm.post_uuid || !createForm.body.trim()"
                            >
                                Post comment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

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
