<template>
    <Head :title="isNew ? 'New post' : 'Edit post'" />
    <AdminLayout>
        <Link href="/admin/posts" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to posts
        </Link>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h1 class="font-sans text-3xl font-semibold tracking-tight">
                    {{ isNew ? 'New post' : 'Edit post' }}
                </h1>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="!isNew"
                        type="button"
                        variant="outline"
                        class="rounded-full border-destructive/40 text-destructive hover:bg-destructive/5"
                        @click="destroy"
                    >
                        <Trash2 class="size-4" /> Delete
                    </Button>
                    <Button
                        type="submit"
                        class="rounded-full bg-ink text-paper hover:bg-ink/90"
                        :disabled="form.processing"
                    >
                        <Save class="size-4" /> {{ isNew ? 'Create' : 'Save' }}
                    </Button>
                </div>
            </div>

            <Card class="rounded-3xl border-border/60 gap-0 p-6">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Author</label>
                        <select
                            v-model="form.user_uuid"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            required
                        >
                            <option v-for="a in authors" :key="a.uuid" :value="a.uuid">
                                {{ a.name }} — {{ a.email }}
                            </option>
                        </select>
                        <p v-if="form.errors.user_uuid" class="mt-1 text-xs text-destructive">{{ form.errors.user_uuid }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[1fr_200px]">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body</label>
                            <Textarea
                                v-model="form.body"
                                rows="10"
                                class="rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
                                placeholder="Write the post body..."
                                required
                            />
                            <p class="mt-1 text-[11px] text-muted-foreground">{{ form.body.length }}/5000</p>
                            <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</label>
                            <select
                                v-model="form.status"
                                class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            >
                                <option value="active">Active</option>
                                <option value="flagged">Flagged</option>
                                <option value="hidden">Hidden</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div v-if="!isNew">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Moderation reason (optional)</label>
                        <Input
                            v-model="form.reason"
                            type="text"
                            class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
                            placeholder="Why are you editing this? Logged for audit."
                        />
                    </div>
                </div>
            </Card>
        </form>
    </AdminLayout>

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
                    @click="confirmDestroy"
                >
                    <Trash2 class="size-4" /> Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, default: null },
    authors: { type: Array, default: () => [] },
});

const page = usePage();
const isNew = computed(() => !props.post);

const form = useForm({
    body: props.post?.body ?? '',
    status: props.post?.status ?? 'active',
    user_uuid: props.post?.user?.uuid ?? props.authors[0]?.uuid ?? '',
    reason: '',
});

const submit = () => {
    if (isNew.value) {
        form.post('/admin/posts', { preserveScroll: true });
    } else {
        form.patch(`/admin/posts/${props.post.uuid}`, { preserveScroll: true });
    }
};

const showDeleteDialog = ref(false);
const destroy = () => { showDeleteDialog.value = true; };
const confirmDestroy = () => {
    showDeleteDialog.value = false;
    form.delete(`/admin/posts/${props.post.uuid}`);
};
</script>
