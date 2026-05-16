<template>
    <Head :title="isNew ? 'New page' : page.title" />
    <AdminLayout>
        <Link href="/admin/pages" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to pages
        </Link>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex items-end justify-between gap-4">
                <h1 class="font-sans text-3xl font-semibold tracking-tight">
                    {{ isNew ? 'New page' : 'Edit page' }}
                </h1>
                <div class="flex items-center gap-2">
                    <select
                        v-model="form.status"
                        class="h-10 rounded-full bg-secondary/60 px-4 text-sm outline-none"
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <Button
                        type="submit"
                        class="rounded-full bg-ink text-paper hover:bg-ink/90"
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
                                class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full text-base"
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
                                class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full font-mono text-sm"
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
                                class="rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary w-full p-4 font-mono text-sm leading-relaxed"
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
import { computed } from 'vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';

const props = defineProps({
    page: { type: Object, default: null },
});

const inertiaPage = usePage();
const isNew = computed(() => !props.page);

const form = useForm({
    slug: props.page?.slug ?? '',
    title: props.page?.title ?? '',
    body: props.page?.body ?? '',
    status: props.page?.status ?? 'draft',
});

const submit = () => {
    if (isNew.value) {
        form.post('/admin/pages', { preserveScroll: true });
    } else {
        form.patch(`/admin/pages/${props.page.uuid}`, { preserveScroll: true });
    }
};
</script>
