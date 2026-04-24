<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, Plus, Search, ShieldBan, Undo2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    bans: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
    bannableUsers: { type: Array, default: () => [] },
});

const page = usePage();
const activeOnly = ref(!!props.filters?.filter?.active);

const toggleActive = () => {
    activeOnly.value = !activeOnly.value;
    router.get('/admin/bans', activeOnly.value ? { filter: { active: 1 } } : {}, {
        preserveState: true, preserveScroll: true, replace: true,
    });
};

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const dateFmt = (iso) => iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
const isActive = (b) => !b.expires_at || new Date(b.expires_at) > new Date();

const lift = (ban) => {
    if (!window.confirm(`Lift ban for ${ban.user?.name}?`)) return;
    router.delete(`/admin/bans/${ban.uuid}`, { preserveScroll: true });
};

const showCreate = ref(false);
const createForm = useForm({ user_uuid: '', reason: '', expires_at: '' });
const submitCreate = () => createForm.post('/admin/bans', {
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; createForm.reset(); userSearch.value = ''; pickerOpen.value = false; },
});

// User picker state (search-as-you-type)
const userSearch = ref('');
const pickerOpen = ref(false);
const selectedUser = computed(() => props.bannableUsers.find((u) => u.uuid === createForm.user_uuid));
const filteredUsers = computed(() => {
    const q = userSearch.value.trim().toLowerCase();
    if (!q) return props.bannableUsers.slice(0, 50);
    return props.bannableUsers
        .filter((u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
        .slice(0, 50);
});
const pickUser = (u) => {
    createForm.user_uuid = u.uuid;
    userSearch.value = '';
    pickerOpen.value = false;
};
const clearUser = () => {
    createForm.user_uuid = '';
    userSearch.value = '';
};
const roleTone = (role) => {
    switch (role) {
        case 'admin': return 'bg-ink/10 text-ink';
        case 'moderator': return 'bg-moss/15 text-moss';
        default: return 'bg-secondary text-muted-foreground';
    }
};
</script>

<template>
    <Head title="Bans" />
    <AdminLayout title="Bans">
        <!-- Stats + filter -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-3">
                <div class="rounded-3xl border border-border/60 bg-card px-5 py-3 shadow-sm">
                    <div class="text-[11px] uppercase tracking-wide text-muted-foreground">Total</div>
                    <div class="font-sans text-2xl font-semibold">{{ counts.all }}</div>
                </div>
                <div class="rounded-3xl border border-border/60 bg-rust/5 px-5 py-3 shadow-sm">
                    <div class="text-[11px] uppercase tracking-wide text-rust">Active</div>
                    <div class="font-sans text-2xl font-semibold text-rust">{{ counts.active }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full bg-card px-4 text-sm shadow-sm hover:bg-secondary">
                    <input :checked="activeOnly" type="checkbox" class="accent-rust" @change="toggleActive" />
                    Active only
                </label>
                <button
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-ink px-4 text-sm font-medium text-paper hover:opacity-90"
                    @click="showCreate = true"
                >
                    <Plus class="size-4" /> New ban
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <ul v-if="bans.data.length" class="divide-y divide-border/60">
                <li
                    v-for="b in bans.data"
                    :key="b.uuid"
                    class="flex items-start gap-4 px-6 py-4"
                >
                    <span class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-2xl bg-rust/10 text-rust">
                        <ShieldBan class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link v-if="b.user" :href="`/admin/users/${b.user.uuid}`" class="font-medium hover:text-rust">
                                {{ b.user?.name ?? 'Deleted user' }}
                            </Link>
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium',
                                    isActive(b) ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground',
                                ]"
                            >
                                {{ isActive(b) ? 'active' : 'lifted' }}
                            </span>
                        </div>
                        <div class="mt-0.5 text-sm">{{ b.reason }}</div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            by {{ b.banned_by?.name ?? 'system' }} · {{ dateFmt(b.created_at) }}
                            <span v-if="b.expires_at"> · expires {{ dateFmt(b.expires_at) }}</span>
                            <span v-else> · permanent</span>
                        </div>
                    </div>
                    <button
                        v-if="isActive(b)"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border px-3 py-1.5 text-xs font-medium hover:bg-secondary"
                        @click="lift(b)"
                    >
                        <Undo2 class="size-3.5" /> Lift
                    </button>
                </li>
            </ul>
            <div v-else class="py-16 text-center text-sm text-muted-foreground">
                No bans.
            </div>

            <div v-if="bans.data.length" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ bans.from }}–{{ bans.to }} of {{ bans.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in bans.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs',
                            link.active ? 'bg-ink text-paper' : link.url ? 'hover:bg-secondary' : 'opacity-40',
                        ]"
                        preserve-scroll
                        preserve-state
                    />
                </div>
            </div>
        </div>

        <!-- Create ban modal -->
        <Teleport to="body">
            <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">New ban</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Pick a user to ban. Already-banned users and super admins are excluded.</p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitCreate">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">User</label>

                            <!-- Selected chip -->
                            <div v-if="selectedUser" class="flex items-center justify-between rounded-2xl bg-secondary p-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-ink text-xs font-semibold text-paper">
                                        {{ initials(selectedUser.name) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 text-sm font-medium">
                                            {{ selectedUser.name }}
                                            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-medium', roleTone(selectedUser.role)]">
                                                {{ selectedUser.role }}
                                            </span>
                                        </div>
                                        <div class="truncate text-[11px] text-muted-foreground">{{ selectedUser.email }}</div>
                                    </div>
                                </div>
                                <button type="button" class="text-xs text-muted-foreground hover:text-ink" @click="clearUser">
                                    Change
                                </button>
                            </div>

                            <!-- Picker dropdown -->
                            <div v-else class="relative">
                                <button
                                    type="button"
                                    class="flex h-11 w-full items-center justify-between rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                                    @click="pickerOpen = !pickerOpen"
                                >
                                    <span class="text-muted-foreground">Select a user…</span>
                                    <ChevronDown class="size-4 text-muted-foreground" />
                                </button>
                                <div
                                    v-if="pickerOpen"
                                    class="absolute left-0 right-0 z-10 mt-1 overflow-hidden rounded-2xl border border-border/60 bg-popover shadow-lg"
                                >
                                    <div class="relative border-b border-border/60">
                                        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <input
                                            v-model="userSearch"
                                            type="search"
                                            placeholder="Search name or email..."
                                            class="h-10 w-full bg-transparent pl-9 pr-3 text-sm outline-none"
                                            autofocus
                                        />
                                    </div>
                                    <ul class="max-h-64 overflow-y-auto">
                                        <li
                                            v-for="u in filteredUsers"
                                            :key="u.uuid"
                                            class="flex cursor-pointer items-center gap-3 px-3 py-2 hover:bg-secondary"
                                            @click="pickUser(u)"
                                        >
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-ink text-[10px] font-semibold text-paper">
                                                {{ initials(u.name) }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 text-sm font-medium">
                                                    <span class="truncate">{{ u.name }}</span>
                                                    <span :class="['rounded-full px-1.5 py-0.5 text-[9px] font-medium', roleTone(u.role)]">
                                                        {{ u.role }}
                                                    </span>
                                                </div>
                                                <div class="truncate text-[11px] text-muted-foreground">{{ u.email }}</div>
                                            </div>
                                        </li>
                                        <li v-if="!filteredUsers.length" class="px-3 py-6 text-center text-xs text-muted-foreground">
                                            No matches.
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <p v-if="createForm.errors.user_uuid" class="mt-1 text-xs text-destructive">{{ createForm.errors.user_uuid }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Reason</label>
                            <textarea
                                v-model="createForm.reason"
                                rows="3"
                                class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                                required
                            />
                            <p v-if="createForm.errors.reason" class="mt-1 text-xs text-destructive">{{ createForm.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Expires at (optional)</label>
                            <input
                                v-model="createForm.expires_at"
                                type="datetime-local"
                                class="w-full rounded-full bg-secondary/60 px-4 py-2 text-sm outline-none focus:bg-secondary"
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="rounded-full px-4 py-2 text-sm hover:bg-secondary" @click="showCreate = false">Cancel</button>
                            <button type="submit" class="rounded-full bg-rust px-4 py-2 text-sm font-medium text-paper hover:opacity-90" :disabled="createForm.processing">
                                Ban user
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
