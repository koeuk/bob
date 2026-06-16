<template>
    <Head :title="isNew ? 'New post' : 'Edit post'" />
    <AdminLayout>
        <Link href="/admin/posts" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-moss">
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
                        class="rounded-full bg-moss text-paper hover:bg-moss/90"
                        :disabled="form.processing"
                    >
                        <Save class="size-4" /> {{ isNew ? 'Create' : 'Save' }}
                    </Button>
                </div>
            </div>

            <Card class="rounded-3xl border-white gap-0 p-6">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Author</label>
                        <Popover v-model:open="openAuthor">
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    role="combobox"
                                    :aria-expanded="openAuthor"
                                    class="h-11 w-full flex items-center justify-between rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                                >
                                    <span :class="form.user_uuid ? '' : 'text-muted-foreground'">{{ selectedAuthorLabel }}</span>
                                    <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
                                </button>
                            </PopoverTrigger>
                            <PopoverContent class="w-80 p-0">
                                <Command>
                                    <CommandInput placeholder="Search author..." />
                                    <CommandList>
                                        <CommandEmpty>No author found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="a in authors"
                                                :key="a.uuid"
                                                :value="a.uuid"
                                                @select="(ev) => { form.user_uuid = ev.detail.value; openAuthor = false }"
                                            >
                                                <Check :class="cn('mr-2 size-4', form.user_uuid === a.uuid ? 'opacity-100' : 'opacity-0')" />
                                                {{ a.name }} — {{ a.email }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <p v-if="form.errors.user_uuid" class="mt-1 text-xs text-destructive">{{ form.errors.user_uuid }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[1fr_200px]">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body</label>
                            <Textarea
                                v-model="form.body"
                                rows="10"
                                class="rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
                                placeholder="Write the post body..."
                                required
                            />
                            <p class="mt-1 text-[11px] text-muted-foreground">{{ form.body.length }}/5000</p>
                            <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</label>
                            <Popover v-model:open="openStatus">
                                <PopoverTrigger as-child>
                                    <button
                                        type="button"
                                        role="combobox"
                                        :aria-expanded="openStatus"
                                        class="h-11 w-full flex items-center justify-between rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                                    >
                                        {{ selectedStatusLabel }}
                                        <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent class="w-48 p-0">
                                    <Command>
                                        <CommandList>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="o in statusOptions"
                                                    :key="o.value"
                                                    :value="o.value"
                                                    @select="(ev) => { form.status = ev.detail.value; openStatus = false }"
                                                >
                                                    <Check :class="cn('mr-2 size-4', form.status === o.value ? 'opacity-100' : 'opacity-0')" />
                                                    {{ o.label }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div v-if="!isNew">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Moderation reason (optional)</label>
                        <Input
                            v-model="form.reason"
                            type="text"
                            class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
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
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, ChevronsUpDown, Save, Trash2 } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, default: null },
    authors: { type: Array, default: () => [] },
});

const isNew = computed(() => !props.post);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'flagged', label: 'Flagged' },
    { value: 'hidden', label: 'Hidden' },
];

const form = useForm({
    body: props.post?.body ?? '',
    status: props.post?.status ?? 'active',
    user_uuid: props.post?.user?.uuid ?? props.authors[0]?.uuid ?? '',
    reason: '',
});

const openAuthor = ref(false);
const openStatus = ref(false);

const selectedAuthorLabel = computed(() => {
    const a = props.authors.find(a => a.uuid === form.user_uuid);
    return a ? `${a.name} — ${a.email}` : 'Select author...';
});

const selectedStatusLabel = computed(() => {
    return statusOptions.find(o => o.value === form.status)?.label ?? 'Select status...';
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
