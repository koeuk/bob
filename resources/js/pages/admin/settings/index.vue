<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps({
    groups: { type: Object, required: true },
});

const page = usePage();

// Flatten groups into editable rows
const rowsFromGroups = () => {
    const rows = [];
    for (const [group, items] of Object.entries(props.groups)) {
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

<template>
    <Head title="Settings" />
    <AdminLayout title="Settings">
        <div class="flex items-center justify-between">
            <p class="max-w-xl text-sm text-muted-foreground">
                App-wide key/value settings. Values are stored as JSON — strings, numbers, booleans, arrays.
            </p>
            <div class="flex items-center gap-2">
                <button
                    class="inline-flex h-10 items-center gap-2 rounded-full border border-border px-4 text-sm font-medium hover:bg-secondary"
                    @click="addRow"
                >
                    <Plus class="size-4" /> Add setting
                </button>
                <button
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-ink px-5 text-sm font-medium text-paper hover:opacity-90"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Save class="size-4" /> Save all
                </button>
            </div>
        </div>

        <div class="space-y-6">
            <div v-for="(rows, group) in groupedRows" :key="group" class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-rust/10 text-xs font-semibold text-rust">
                        {{ group.slice(0, 1).toUpperCase() }}
                    </span>
                    <h3 class="text-lg font-semibold capitalize">{{ group }}</h3>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="row in rows"
                        :key="row.key + row.group"
                        class="grid grid-cols-[1fr_2fr_auto] items-center gap-3"
                    >
                        <input
                            v-model="row.key"
                            type="text"
                            placeholder="key"
                            class="h-10 rounded-full bg-secondary/60 px-4 font-mono text-sm outline-none focus:bg-secondary"
                        />
                        <input
                            v-model="row.value"
                            type="text"
                            placeholder="value"
                            class="h-10 rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                        />
                        <button
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                            @click="removeRow(row)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!state.rows.length" class="rounded-3xl border border-border/60 bg-card py-16 text-center text-sm text-muted-foreground shadow-sm">
            No settings yet. Click &ldquo;Add setting&rdquo; to create one.
        </div>
    </AdminLayout>
</template>
