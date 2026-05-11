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
                <!-- Avatar picker -->
                <div class="mb-6 flex items-center gap-5">
                    <div class="relative size-20 shrink-0">
                        <div class="flex size-20 items-center justify-center overflow-hidden rounded-full bg-ink text-xl font-semibold text-paper">
                            <img v-if="avatarPreview" :src="avatarPreview" class="size-20 object-cover" alt="Avatar preview" />
                            <template v-else>{{ initials(form.name) }}</template>
                        </div>
                        <button
                            type="button"
                            class="absolute bottom-0 right-0 flex size-7 items-center justify-center rounded-full bg-card border border-border/60 shadow-sm hover:bg-secondary"
                            @click="$refs.avatarInput.click()"
                        >
                            <Camera class="size-3.5 text-muted-foreground" />
                        </button>
                        <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                    </div>
                    <div>
                        <p class="text-sm font-medium">Profile photo</p>
                        <p class="text-xs text-muted-foreground">JPG, PNG or GIF · max 2 MB</p>
                        <button
                            v-if="avatarPreview"
                            type="button"
                            class="mt-1 text-xs text-rust hover:underline"
                            @click="clearAvatar"
                        >Remove</button>
                    </div>
                </div>

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

                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Password{{ isNew ? '' : ' (leave blank to keep)' }}
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="h-11 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                            placeholder="Minimum 8 characters"
                            :required="isNew"
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

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Camera, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
});

const page = usePage();
const isNew = computed(() => !props.user);
const currentRole = computed(() => page.props.auth?.user?.role);
const canCreateAdmin = computed(() => currentRole.value === 'super_admin');

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const avatarInput = ref(null);
const avatarPreview = ref(null);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    role: props.user?.role ?? 'user',
    avatar: null,
});

const onAvatarChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const clearAvatar = () => {
    form.avatar = null;
    avatarPreview.value = null;
    if (avatarInput.value) avatarInput.value.value = '';
};

const submit = () => {
    if (isNew.value) {
        form.post('/admin/users', { preserveScroll: true, forceFormData: true });
    } else {
        form.patch(`/admin/users/${props.user.uuid}`, { preserveScroll: true, forceFormData: true });
    }
};
</script>
