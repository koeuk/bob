<template>
    <Head :title="isNew ? 'New page' : page.title" />
    <AdminLayout>
        <Link href="/admin/pages" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-moss">
            <ArrowLeft class="size-4" /> Back to pages
        </Link>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex items-end justify-between gap-4">
                <h1 class="font-sans text-3xl font-semibold tracking-tight">
                    {{ isNew ? 'New page' : 'Edit page' }}
                </h1>
                <div class="flex items-center gap-2">
                    <Popover v-model:open="openStatus">
                        <PopoverTrigger as-child>
                            <button
                                type="button"
                                role="combobox"
                                :aria-expanded="openStatus"
                                class="h-10 inline-flex items-center gap-2 rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                            >
                                {{ statusLabel }}
                                <ChevronsUpDown class="size-3.5 text-muted-foreground" />
                            </button>
                        </PopoverTrigger>
                        <PopoverContent class="w-40 p-0">
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
                    <Button
                        type="submit"
                        class="rounded-full bg-moss text-paper hover:bg-moss/90"
                        :disabled="form.processing"
                    >
                        <Save class="size-4" /> {{ isNew ? 'Create' : 'Save' }}
                    </Button>
                </div>
            </div>

            <Card class="rounded-3xl border-border/60 gap-0">
                <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Page details</CardTitle></CardHeader>
                <CardContent class="px-6 pb-6 pt-4">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Title</label>
                            <Input
                                v-model="form.title"
                                type="text"
                                class="h-11 rounded-2xl bg-secondary/60 border-border/60 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full text-base"
                                placeholder="About us"
                                required
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Slug</label>
                            <Input
                                v-model="form.slug"
                                type="text"
                                class="h-11 rounded-2xl bg-secondary/60 border-border/60 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full font-mono text-sm"
                                placeholder="about-us"
                                required
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body (markdown)</label>
                            <Textarea
                                v-model="form.body"
                                rows="18"
                                class="rounded-2xl bg-secondary/60 border-border/60 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full p-4 font-mono text-sm leading-relaxed"
                                placeholder="# About us..."
                                required
                            />
                            <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div v-if="!isNew && page?.updated_by" class="text-xs text-muted-foreground">
                Last updated by {{ page.updated_by.name }} on {{ new Date(page.updated_at).toLocaleDateString() }}.
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandGroup, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-vue-next';

const props = defineProps({
    page: { type: Object, default: null },
});

const inertiaPage = usePage();
const isNew = computed(() => !props.page);

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
];
const openStatus = ref(false);

const form = useForm({
    slug: props.page?.slug ?? '',
    title: props.page?.title ?? '',
    body: props.page?.body ?? '',
    status: props.page?.status ?? 'draft',
});

const statusLabel = computed(() => statusOptions.find(o => o.value === form.status)?.label ?? 'Draft');

const submit = () => {
    if (isNew.value) {
        form.post('/admin/pages', { preserveScroll: true });
    } else {
        form.patch(`/admin/pages/${props.page.uuid}`, { preserveScroll: true });
    }
};
</script>
