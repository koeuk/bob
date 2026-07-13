<template>
    <Head title="Settings" />
    <AdminLayout title="Settings">
        <!-- Branding -->
        <Card class="rounded-3xl border-0 gap-0">
            <CardHeader class="px-6 pt-6 pb-0">
                <CardTitle class="flex items-center gap-2">
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-forest text-xs font-semibold text-paper">
                        B
                    </span>
                    <span>Branding</span>
                </CardTitle>
                <p class="mt-1 text-sm text-muted-foreground">
                    Set the app name and logo shown across the sidebar and headers.
                </p>
            </CardHeader>
            <CardContent class="px-6 pb-6 pt-5">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <!-- Logo -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex size-24 items-center justify-center overflow-hidden rounded-3xl border-2 border-white bg-secondary shadow-sm ring-1 ring-black/5 dark:border-white/10 dark:ring-white/10">
                            <img v-if="logoPreview" :src="logoPreview" alt="App logo" class="size-full object-cover" />
                            <span v-else class="font-serif text-4xl leading-none text-forest">{{ initial }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" @change="onLogoChange" />
                            <Button variant="outline" size="sm" class="rounded-full" @click="fileInput?.click()">
                                <Upload class="size-4" /> Upload
                            </Button>
                            <button
                                v-if="logoPreview"
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                @click="removeLogo"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Name + save -->
                    <div class="flex-1 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">App name</label>
                            <Input
                                v-model="brandingForm.app_name"
                                type="text"
                                placeholder="bob"
                                class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-sm hover:border hover:border-border focus-visible:ring-0 text-sm"
                            />
                            <p v-if="brandingForm.errors.app_name" class="text-xs text-destructive">{{ brandingForm.errors.app_name }}</p>
                            <p v-if="brandingForm.errors.logo" class="text-xs text-destructive">{{ brandingForm.errors.logo }}</p>
                            <p class="text-xs text-muted-foreground">PNG, JPG, WEBP or SVG. Max 2 MB.</p>
                        </div>
                        <Button
                            class="rounded-full bg-moss text-paper hover:bg-moss/90"
                            :disabled="brandingForm.processing"
                            @click="submitBranding"
                        >
                            <Save class="size-4" /> Save branding
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="flex items-center justify-between">
            <p class="max-w-xl text-sm text-muted-foreground">
                App-wide key/value settings. Values are stored as JSON — strings, numbers, booleans, arrays.
            </p>
            <div class="flex items-center gap-2">
                <Button variant="outline" class="rounded-full" @click="addRow">
                    <Plus class="size-4" /> Add setting
                </Button>
                <Button
                    class="rounded-full bg-moss text-paper hover:bg-moss/90"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Save class="size-4" /> Save all
                </Button>
            </div>
        </div>

        <div class="space-y-6">
            <Card v-for="(rows, group) in groupedRows" :key="group" class="rounded-3xl border-0 gap-0">
                <CardHeader class="px-6 pt-6 pb-0">
                    <CardTitle class="flex items-center gap-2">
                        <span class="inline-flex size-8 items-center justify-center rounded-full bg-forest text-xs font-semibold text-paper">
                            {{ group.slice(0, 1).toUpperCase() }}
                        </span>
                        <span class="capitalize">{{ group }}</span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-6 pb-6 pt-4">
                    <div class="space-y-3">
                        <div
                            v-for="row in rows"
                            :key="row.key + row.group"
                            class="grid grid-cols-[1fr_2fr_auto] items-center gap-3"
                        >
                            <Input
                                v-model="row.key"
                                type="text"
                                placeholder="key"
                                class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-sm hover:border hover:border-border focus-visible:ring-0 font-mono text-sm"
                            />
                            <Input
                                v-model="row.value"
                                type="text"
                                placeholder="value"
                                class="h-11 rounded-2xl bg-secondary/60 border-0 shadow-sm hover:border hover:border-border focus-visible:ring-0 text-sm"
                            />
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                @click="removeRow(row)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-if="!state.rows.length" class="rounded-3xl border-2 border-0 dark:border-0/10 shadow-sm bg-card py-16 text-center text-sm text-muted-foreground">
            No settings yet. Click &ldquo;Add setting&rdquo; to create one.
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Save, Trash2, Upload } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';

const props = defineProps({
    groups: { type: Object, required: true },
    branding: { type: Object, default: () => ({ name: '', logo: null }) },
});

const page = usePage();

// ─── Branding ──────────────────────────────────────────────────────────
const fileInput = ref(null);
const localPreview = ref(null); // object URL for a freshly picked file

const brandingForm = useForm({
    app_name: props.branding.name ?? '',
    logo: null,
    remove_logo: false,
});

const logoPreview = computed(() => localPreview.value ?? props.branding.logo ?? null);
const initial = computed(() => (brandingForm.app_name || 'b').slice(0, 1).toLowerCase());

const onLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    brandingForm.logo = file;
    brandingForm.remove_logo = false;
    localPreview.value = URL.createObjectURL(file);
};

const removeLogo = () => {
    brandingForm.logo = null;
    brandingForm.remove_logo = true;
    localPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const submitBranding = () => {
    brandingForm.post('/admin/settings/branding', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            localPreview.value = null;
            brandingForm.logo = null;
            brandingForm.remove_logo = false;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

// ─── Generic key/value settings (branding group is managed above) ──────
// Flatten groups into editable rows
const rowsFromGroups = () => {
    const rows = [];
    for (const [group, items] of Object.entries(props.groups)) {
        if (group === 'branding') continue;
        for (const s of items) {
            rows.push({
                key: s.key,
                group,
                value: typeof s.value === 'string' ? s.value : JSON.stringify(s.value ?? ''),
            });
        }
    }
    return rows;
};

const state = reactive({ rows: rowsFromGroups() });

const groupedRows = computed(() => {
    const g = {};
    for (const r of state.rows) {
        g[r.group] = g[r.group] ?? [];
        g[r.group].push(r);
    }
    return g;
});

const addRow = () => {
    state.rows.push({ key: '', group: 'general', value: '' });
};
const removeRow = (row) => {
    state.rows.splice(state.rows.indexOf(row), 1);
};

const form = useForm({ settings: [] });
const submit = () => {
    form.settings = state.rows
        .filter((r) => r.key)
        .map((r) => ({ key: r.key, group: r.group, value: r.value }));
    form.patch('/admin/settings', { preserveScroll: true });
};
</script>
