<template>
    <Head :title="isNew ? 'New user' : `Edit · ${user.name}`" />
    <AdminLayout>
        <Link :href="isNew ? '/admin/users' : `/admin/users/${user.uuid}`" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-moss transition-colors">
            <ArrowLeft class="size-4" /> {{ isNew ? 'Back to users' : 'Back to profile' }}
        </Link>

        <!-- ── CREATE: single flat form ── -->
        <template v-if="isNew">
            <Card class="rounded-3xl border-white gap-0 p-6">
                <form class="space-y-6" @submit.prevent="submitCreate">
                    <!-- Avatar -->
                    <div class="flex items-center gap-5">
                        <div class="relative size-16 shrink-0">
                            <div class="flex size-16 items-center justify-center overflow-hidden rounded-2xl bg-forest text-base font-semibold text-paper">
                                <img v-if="avatarSrc" :src="avatarSrc" class="size-16 object-cover" alt="" />
                                <template v-else>{{ initials(createForm.name) }}</template>
                            </div>
                            <button type="button" class="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-card border-2 border-white dark:border-white/10 shadow-sm shadow-sm hover:bg-secondary transition-colors" @click="$refs.avatarInput.click()">
                                <Camera class="size-3 text-muted-foreground" />
                            </button>
                            <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">Profile photo</p>
                            <p class="text-xs text-muted-foreground mt-0.5">JPG, PNG or GIF · max 2 MB</p>
                            <button v-if="avatarSrc" type="button" class="mt-1 text-xs text-rust hover:underline" @click="clearAvatar">Remove</button>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Name</label>
                            <Input v-model="createForm.name" type="text" required class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                            <p v-if="createForm.errors.name" class="mt-1 text-xs text-destructive">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</label>
                            <Input v-model="createForm.email" type="email" required class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                            <p v-if="createForm.errors.email" class="mt-1 text-xs text-destructive">{{ createForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Password</label>
                            <Input v-model="createForm.password" type="password" required minlength="8" placeholder="Minimum 8 characters" class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                            <p v-if="createForm.errors.password" class="mt-1 text-xs text-destructive">{{ createForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Role</label>
                            <Popover v-model:open="openCreateRole">
                                <PopoverTrigger as-child>
                                    <button
                                        type="button"
                                        role="combobox"
                                        :aria-expanded="openCreateRole"
                                        class="h-11 w-full flex items-center justify-between rounded-2xl bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary transition-colors"
                                    >
                                        {{ createRoleLabel }}
                                        <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent class="w-48 p-0">
                                    <Command>
                                        <CommandList>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="o in createRoleOptions"
                                                    :key="o.value"
                                                    :value="o.value"
                                                    @select="(ev) => { createForm.role = ev.detail.value; openCreateRole = false }"
                                                >
                                                    <CheckIcon :class="cn('mr-2 size-4', createForm.role === o.value ? 'opacity-100' : 'opacity-0')" />
                                                    {{ o.label }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="!canCreateAdmin" class="mt-1 text-[11px] text-muted-foreground">Only super admins can create admin-level accounts.</p>
                            <p v-if="createForm.errors.role" class="mt-1 text-xs text-destructive">{{ createForm.errors.role }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button type="submit" :disabled="createForm.processing" class="rounded-full bg-moss text-paper hover:bg-moss/90">
                            <Save class="size-4" /> Create user
                        </Button>
                    </div>
                </form>
            </Card>
        </template>

        <!-- ── EDIT: stepped form ── -->
        <template v-else>
            <Card class="rounded-3xl border-white gap-0 overflow-hidden">
                <!-- Step tabs -->
                <div class="border-b border-white px-6 py-5">
                    <div class="flex items-center gap-0">
                        <template v-for="(s, i) in visibleSteps" :key="s.key">
                            <button
                                class="flex items-center gap-2.5 rounded-2xl px-3 py-2 transition-all duration-200"
                                :class="currentStep === s.key
                                    ? 'bg-moss text-paper shadow-sm'
                                    : 'text-muted-foreground hover:text-moss hover:bg-secondary/60'"
                                @click="currentStep = s.key"
                            >
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition-colors"
                                    :class="currentStep === s.key ? 'bg-paper/20 text-paper' : 'bg-secondary text-muted-foreground'"
                                >
                                    <CheckIcon v-if="completedSteps.includes(s.key)" class="size-3.5" />
                                    <span v-else>{{ i + 1 }}</span>
                                </span>
                                <span class="text-sm font-medium">{{ s.label }}</span>
                            </button>
                            <div v-if="i < visibleSteps.length - 1" class="h-px w-4 shrink-0 bg-border/60" />
                        </template>
                    </div>
                </div>

                <!-- Step content -->
                <div class="p-6">

                    <!-- Step 1: Profile -->
                    <form v-if="currentStep === 'profile'" class="space-y-5" @submit.prevent="submitProfile">
                        <div class="flex items-start gap-5">
                            <div class="relative size-16 shrink-0">
                                <div class="flex size-16 items-center justify-center overflow-hidden rounded-2xl bg-forest text-base font-semibold text-paper">
                                    <img v-if="avatarSrc" :src="avatarSrc" class="size-16 object-cover" alt="" />
                                    <template v-else>{{ initials(profileForm.name || user.name) }}</template>
                                </div>
                                <button type="button" class="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-card border-2 border-white dark:border-white/10 shadow-sm shadow-sm hover:bg-secondary transition-colors" @click="$refs.avatarInput.click()">
                                    <Camera class="size-3 text-muted-foreground" />
                                </button>
                                <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">Profile photo</p>
                                <p class="text-xs text-muted-foreground mt-0.5">JPG, PNG or GIF · max 2 MB</p>
                                <button v-if="avatarSrc" type="button" class="mt-1 text-xs text-rust hover:underline" @click="clearAvatar">Remove</button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Name</label>
                                <Input v-model="profileForm.name" type="text" class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                                <p v-if="profileForm.errors.name" class="mt-1 text-xs text-destructive">{{ profileForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</label>
                                <Input v-model="profileForm.email" type="email" class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                                <p v-if="profileForm.errors.email" class="mt-1 text-xs text-destructive">{{ profileForm.errors.email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <p v-if="profileSaved" class="flex items-center gap-1.5 text-sm text-moss font-medium">
                                <CheckIcon class="size-4" /> Profile saved
                            </p>
                            <span v-else />
                            <div class="flex items-center gap-2">
                                <Button type="submit" :disabled="profileForm.processing || !profileForm.isDirty" class="rounded-full bg-moss text-paper hover:bg-moss/90">
                                    <Save class="size-4" /> Save profile
                                </Button>
                                <Button type="button" variant="outline" class="rounded-full" @click="goNext">
                                    Next <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </form>

                    <!-- Step 2: Security -->
                    <form v-if="currentStep === 'security'" class="space-y-5" @submit.prevent="submitSecurity">
                        <div>
                            <p class="text-sm text-muted-foreground mb-4">Set a new password for this user. Leave blank to keep the current password.</p>
                            <div class="max-w-sm">
                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground">New password</label>
                                <Input v-model="securityForm.password" type="password" placeholder="Minimum 8 characters" minlength="8" class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                                <p v-if="securityForm.errors.password" class="mt-1 text-xs text-destructive">{{ securityForm.errors.password }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <p v-if="securitySaved" class="flex items-center gap-1.5 text-sm text-moss font-medium">
                                <CheckIcon class="size-4" /> Password updated
                            </p>
                            <span v-else />
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="outline" class="rounded-full" @click="goPrev">
                                    <ChevronLeft class="size-4" /> Back
                                </Button>
                                <Button type="submit" :disabled="securityForm.processing || !securityForm.password" class="rounded-full bg-moss text-paper hover:bg-moss/90">
                                    <Save class="size-4" /> Update password
                                </Button>
                                <Button v-if="canAssignRole" type="button" variant="outline" class="rounded-full" @click="goNext">
                                    Next <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </form>

                    <!-- Step 3: Role -->
                    <form v-if="currentStep === 'role'" class="space-y-5" @submit.prevent="submitRole">
                        <div>
                            <p class="text-sm text-muted-foreground mb-4">Change this user's role. This affects what they can access across the platform.</p>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <label
                                    v-for="r in availableRoles"
                                    :key="r.value"
                                    :class="[
                                        'relative flex cursor-pointer flex-col gap-1.5 rounded-2xl border-2 p-4 transition-all duration-150',
                                        roleForm.role === r.value
                                            ? 'border-moss bg-moss/5'
                                            : 'border-white bg-secondary/30 hover:border-border hover:bg-secondary/60',
                                    ]"
                                >
                                    <input type="radio" :value="r.value" v-model="roleForm.role" class="sr-only" />
                                    <component :is="r.icon" class="size-5" :class="roleForm.role === r.value ? 'text-moss' : 'text-muted-foreground'" />
                                    <span class="text-sm font-semibold" :class="roleForm.role === r.value ? 'text-moss' : 'text-ink/70'">{{ r.label }}</span>
                                    <span class="text-[11px] text-muted-foreground leading-snug">{{ r.desc }}</span>
                                    <span v-if="roleForm.role === r.value" class="absolute right-3 top-3 flex size-4 items-center justify-center rounded-full bg-moss">
                                        <CheckIcon class="size-2.5 text-paper" />
                                    </span>
                                </label>
                            </div>
                            <p v-if="roleForm.errors.role" class="mt-2 text-xs text-destructive">{{ roleForm.errors.role }}</p>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <p v-if="roleSaved" class="flex items-center gap-1.5 text-sm text-moss font-medium">
                                <CheckIcon class="size-4" /> Role updated
                            </p>
                            <span v-else />
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="outline" class="rounded-full" @click="goPrev">
                                    <ChevronLeft class="size-4" /> Back
                                </Button>
                                <Button type="submit" :disabled="roleForm.processing || roleForm.role === user.role" class="rounded-full bg-moss text-paper hover:bg-moss/90">
                                    <Save class="size-4" /> Save role
                                </Button>
                            </div>
                        </div>
                    </form>

                </div>
            </Card>
        </template>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandGroup, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { ArrowLeft, Camera, Check as CheckIcon, ChevronsUpDown, ChevronLeft, ChevronRight, Crown, Save, Shield, User, UserCog } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
});

const page = usePage();
const isNew = computed(() => !props.user);
const currentRole = computed(() => page.props.auth?.user?.role);
const canCreateAdmin = computed(() => currentRole.value === 'super_admin');
const canAssignRole = computed(() => currentRole.value === 'super_admin');

const openCreateRole = ref(false);
const createRoleOptions = computed(() => [
    { value: 'user', label: 'User' },
    { value: 'moderator', label: 'Moderator' },
    ...(canCreateAdmin.value ? [{ value: 'admin', label: 'Admin' }, { value: 'super_admin', label: 'Super Admin' }] : []),
]);
const createRoleLabel = computed(() => createRoleOptions.value.find(o => o.value === createForm.role)?.label ?? 'Select role...');

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

// Shared avatar state
const avatarInput = ref(null);
const avatarPreview = ref(null);
const avatarRemoved = ref(false);

// Show the newly picked file if there is one, otherwise the user's stored
// photo — without this the edit screen always rendered initials, as if the
// user had no avatar, and offered no way to delete the stored one.
const avatarSrc = computed(() => {
    if (avatarRemoved.value) return null;
    return avatarPreview.value ?? props.user?.avatar ?? null;
});

const onAvatarChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    createForm.avatar = file;
    profileForm.avatar = file;
    profileForm.remove_avatar = false;
    avatarRemoved.value = false;
    avatarPreview.value = URL.createObjectURL(file);
};
const clearAvatar = () => {
    createForm.avatar = null;
    profileForm.avatar = null;
    // Stage removal of the stored photo; applied on save.
    profileForm.remove_avatar = true;
    avatarRemoved.value = true;
    avatarPreview.value = null;
    if (avatarInput.value) avatarInput.value.value = '';
};

// ── CREATE ──
const createForm = useForm({ name: '', email: '', password: '', role: 'user', avatar: null });
const submitCreate = () => createForm.post('/admin/users', { forceFormData: true });

// ── EDIT steps ──
const allSteps = [
    { key: 'profile',  label: 'Profile' },
    { key: 'security', label: 'Security' },
    { key: 'role',     label: 'Role' },
];
const visibleSteps = computed(() => allSteps.filter(s => s.key !== 'role' || canAssignRole.value));
const currentStep = ref('profile');
const completedSteps = ref([]);

const stepKeys = computed(() => visibleSteps.value.map(s => s.key));
const currentIndex = computed(() => stepKeys.value.indexOf(currentStep.value));
const goNext = () => { if (currentIndex.value < stepKeys.value.length - 1) currentStep.value = stepKeys.value[currentIndex.value + 1] };
const goPrev = () => { if (currentIndex.value > 0) currentStep.value = stepKeys.value[currentIndex.value - 1] };

// Step 1 — Profile
const profileSaved = ref(false);
const profileForm = useForm({ name: props.user?.name ?? '', email: props.user?.email ?? '', avatar: null, remove_avatar: false });
const submitProfile = () => {
    profileForm.patch(`/admin/users/${props.user.uuid}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            profileSaved.value = true;
            if (!completedSteps.value.includes('profile')) completedSteps.value.push('profile');
            setTimeout(() => { profileSaved.value = false }, 3000);
        },
    });
};

// Step 2 — Security
const securitySaved = ref(false);
const securityForm = useForm({ password: '' });
const submitSecurity = () => {
    securityForm.patch(`/admin/users/${props.user.uuid}`, {
        preserveScroll: true,
        onSuccess: () => {
            securitySaved.value = true;
            securityForm.reset();
            if (!completedSteps.value.includes('security')) completedSteps.value.push('security');
            setTimeout(() => { securitySaved.value = false }, 3000);
        },
    });
};

// Step 3 — Role
const roleSaved = ref(false);
const roleForm = useForm({ role: props.user?.role ?? 'user' });
const submitRole = () => {
    roleForm.post(`/admin/users/${props.user.uuid}/role`, {
        preserveScroll: true,
        onSuccess: () => {
            roleSaved.value = true;
            if (!completedSteps.value.includes('role')) completedSteps.value.push('role');
            setTimeout(() => { roleSaved.value = false }, 3000);
        },
    });
};

const availableRoles = [
    { value: 'user',        label: 'User',        icon: User,    desc: 'Standard access, can post and comment.' },
    { value: 'moderator',   label: 'Moderator',   icon: Shield,  desc: 'Can review reports and hide content.' },
    { value: 'admin',       label: 'Admin',       icon: UserCog, desc: 'Full access except super admin actions.' },
    { value: 'super_admin', label: 'Super Admin', icon: Crown,   desc: 'Unrestricted access to all features.' },
];
</script>
