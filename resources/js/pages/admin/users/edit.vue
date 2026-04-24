<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
});

const page = usePage();
const isNew = computed(() => !props.user);
const currentRole = computed(() => page.props.auth?.user?.role);
const canCreateAdmin = computed(() => currentRole.value === 'super_admin');

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    role: props.user?.role ?? 'user',
});

const submit = () => {
    if (isNew.value) {
        form.post('/admin/users', { preserveScroll: true });
    } else {
        form.patch(`/admin/users/${props.user.uuid}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isNew ? 'New user' : 'Edit user'" />
    <AdminLayout>
        <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to users
        </Link>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex items-end justify-between gap-4">
                <h1 class="font-sans text-3xl font-semibold tracking-tight">
                    {{ isNew ? 'New user' : 'Edit user' }}
                </h1>
                <button
                    type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-ink px-5 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                    :disabled="form.processing"
                >
                    <Save class="size-4" /> {{ isNew ? 'Create user' : 'Save' }}
                </button>
            </div>

            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            required
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div v-if="isNew">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            placeholder="Minimum 8 characters"
                            required
                            minlength="8"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div v-if="isNew">
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Role</label>
                        <select
                            v-model="form.role"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                        >
                            <option value="user">User</option>
                            <option value="moderator">Moderator</option>
                            <option v-if="canCreateAdmin" value="admin">Admin</option>
                            <option v-if="canCreateAdmin" value="super_admin">Super Admin</option>
                        </select>
                        <p v-if="!canCreateAdmin" class="mt-1 text-[11px] text-muted-foreground">
                            Only super admins can create admin-level accounts.
                        </p>
                        <p v-if="form.errors.role" class="mt-1 text-xs text-destructive">{{ form.errors.role }}</p>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
