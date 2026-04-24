<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

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
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 rounded-full bg-ink px-5 text-sm font-medium text-paper hover:opacity-90"
                        :disabled="form.processing"
                    >
                        <Save class="size-4" /> {{ isNew ? 'Create' : 'Save' }}
                    </button>
                </div>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-base outline-none focus:bg-secondary"
                            placeholder="About us"
                            required
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Slug</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 font-mono text-sm outline-none focus:bg-secondary"
                            placeholder="about-us"
                            required
                        />
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Body (markdown)</label>
                        <textarea
                            v-model="form.body"
                            rows="18"
                            class="w-full rounded-2xl bg-secondary/60 p-4 font-mono text-sm leading-relaxed outline-none focus:bg-secondary"
                            placeholder="# About us..."
                            required
                        />
                        <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
                    </div>
                </div>
            </div>

            <div v-if="!isNew && page?.updated_by" class="text-xs text-muted-foreground">
                Last updated by {{ page.updated_by.name }} on {{ new Date(page.updated_at).toLocaleDateString() }}.
            </div>
        </form>
    </AdminLayout>
</template>
