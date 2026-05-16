<template>
    <Head title="Post" />

    <Dialog :open="showDeleteDialog" @update:open="(v) => { showDeleteDialog = v }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Delete post?</DialogTitle>
                <DialogDescription>
                    This will soft-delete the post and remove it from public view. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="showDeleteDialog = false">
                    Cancel
                </Button>
                <Button
                    class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="confirmDelete"
                >
                    <Trash2 class="size-4" /> Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <AdminLayout>
        <div class="flex items-center justify-between">
            <Link href="/admin/posts" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
                <ArrowLeft class="size-4" /> Back to posts
            </Link>
            <Button as-child variant="outline" class="rounded-full">
                <Link :href="`/admin/posts/${post.uuid}/edit`">
                    <Pencil class="size-4" /> Edit
                </Link>
            </Button>
        </div>

        <Card class="rounded-3xl border-border/60 gap-0 p-6">
            <header class="mb-4 flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-full bg-ink text-sm font-semibold text-paper">
                        {{ initials(post.user?.name) }}
                    </span>
                    <div>
                        <Link v-if="post.user" :href="`/admin/users/${post.user.uuid}`" class="font-medium hover:text-rust">
                            {{ post.user.name }}
                        </Link>
                        <div class="text-xs text-muted-foreground">{{ dateFmt(post.created_at) }}</div>
                    </div>
                </div>
                <Badge :class="['rounded-full border-0', statusTone]">{{ post.status }}</Badge>
            </header>

            <div class="whitespace-pre-wrap text-base leading-relaxed">{{ post.body }}</div>

            <div class="mt-6 flex items-center gap-6 text-sm text-muted-foreground">
                <span class="inline-flex items-center gap-1.5"><Heart class="size-4" /> {{ post.likes_count ?? 0 }}</span>
                <span class="inline-flex items-center gap-1.5"><MessageCircle class="size-4" /> {{ post.comments?.length ?? 0 }}</span>
                <span v-if="post.reports?.length" class="inline-flex items-center gap-1.5 text-rust"><Flag class="size-4" /> {{ post.reports.length }}</span>
            </div>
        </Card>

        <!-- Moderation actions -->
        <Card class="rounded-3xl border-border/60 gap-0 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="mr-2 text-xs uppercase tracking-wide text-muted-foreground">Moderate</span>
                <Button
                    :disabled="post.status === 'active'"
                    class="rounded-full bg-moss text-paper hover:bg-moss/90"
                    @click="setStatus('active')"
                >
                    <Eye class="size-4" /> Active
                </Button>
                <Button
                    :disabled="post.status === 'flagged'"
                    class="rounded-full bg-rust text-paper hover:bg-rust/90"
                    @click="setStatus('flagged')"
                >
                    <Flag class="size-4" /> Flag
                </Button>
                <Button
                    :disabled="post.status === 'hidden'"
                    class="rounded-full bg-ink text-paper hover:bg-ink/90"
                    @click="setStatus('hidden')"
                >
                    <EyeOff class="size-4" /> Hide
                </Button>
                <div class="mx-2 h-6 w-px bg-border"></div>
                <Button
                    variant="outline"
                    class="rounded-full border-destructive/40 text-destructive hover:bg-destructive/5"
                    @click="deletePost"
                >
                    <Trash2 class="size-4" /> Delete
                </Button>
            </div>
        </Card>

        <!-- Reports for this post -->
        <Card v-if="post.reports?.length" class="rounded-3xl border-border/60 gap-0">
            <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Reports on this post</CardTitle></CardHeader>
            <CardContent class="px-6 pb-6 pt-4">
                <ul class="divide-y divide-border/60">
                    <li v-for="r in post.reports" :key="r.uuid" class="flex items-start justify-between gap-3 py-3 text-sm">
                        <div>
                            <div class="font-medium">
                                <Link :href="`/admin/reports/${r.uuid}`" class="hover:text-rust">{{ r.reason }}</Link>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                by {{ r.reporter?.name ?? 'unknown' }} · {{ dateFmt(r.created_at) }}
                            </div>
                        </div>
                        <Badge class="rounded-full border-0 bg-secondary text-muted-foreground">{{ r.status }}</Badge>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Comments -->
        <Card v-if="post.comments?.length" class="rounded-3xl border-border/60 gap-0">
            <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Comments ({{ post.comments.length }})</CardTitle></CardHeader>
            <CardContent class="px-6 pb-6 pt-4">
                <ul class="divide-y divide-border/60">
                    <li v-for="c in post.comments" :key="c.uuid" class="flex items-start gap-3 py-3 text-sm">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold">
                            {{ initials(c.user?.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ c.user?.name ?? 'unknown' }}</span>
                                <span class="text-[11px] text-muted-foreground">{{ dateFmt(c.created_at) }}</span>
                            </div>
                            <p class="mt-0.5">{{ c.body }}</p>
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/Badge.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, Flag, Heart, MessageCircle, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
});

const page = usePage();

const flagForm = useForm({ status: props.post.status });
const setStatus = (newStatus) => {
    flagForm.status = newStatus;
    flagForm.patch(`/admin/posts/${props.post.uuid}/flag`, { preserveScroll: true });
};

const showDeleteDialog = ref(false);
const deletePost = () => { showDeleteDialog.value = true; };
const confirmDelete = () => {
    showDeleteDialog.value = false;
    router.delete(`/admin/posts/${props.post.uuid}`);
};

const dateFmt = (iso) => new Date(iso).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const statusTone = computed(() => {
    switch (props.post.status) {
        case 'flagged': return 'bg-rust/15 text-rust';
        case 'hidden': return 'bg-ink/10 text-ink';
        default: return 'bg-moss/15 text-moss';
    }
});
</script>
