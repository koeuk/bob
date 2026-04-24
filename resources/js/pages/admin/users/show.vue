<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Crown, Hash, Mail, Save, ShieldBan, Trash2, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    reportsAgainst: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const canAssignRole = computed(() => currentUser.value?.role === 'super_admin');

const activeBans = computed(() => (props.user.bans ?? []).filter((b) => !b.expires_at || new Date(b.expires_at) > new Date()));
const isBanned = computed(() => activeBans.value.length > 0);

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const dateFmt = (iso) => iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const banForm = useForm({ reason: '', expires_at: '' });
const showBanModal = ref(false);

const submitBan = () => banForm.post(`/admin/users/${props.user.uuid}/ban`, {
    preserveScroll: true,
    onSuccess: () => { showBanModal.value = false; banForm.reset(); },
});

const unban = () => router.post(`/admin/users/${props.user.uuid}/unban`, {}, { preserveScroll: true });

const roleForm = useForm({ role: props.user.role });
const submitRole = () => roleForm.post(`/admin/users/${props.user.uuid}/role`, { preserveScroll: true });

const profileForm = useForm({ name: props.user.name, email: props.user.email });
const submitProfile = () => profileForm.patch(`/admin/users/${props.user.uuid}`, { preserveScroll: true });

const deleteUser = () => {
    if (!window.confirm(`Delete ${props.user.name}? This cannot be undone.`)) return;
    router.delete(`/admin/users/${props.user.uuid}`);
};

const roleClasses = (role) => {
    switch (role) {
        case 'super_admin': return 'bg-rust/15 text-rust';
        case 'admin': return 'bg-ink/10 text-ink';
        case 'moderator': return 'bg-moss/15 text-moss';
        default: return 'bg-secondary text-muted-foreground';
    }
};
</script>

<template>
    <Head :title="user.name" />
    <AdminLayout>
        <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-ink">
            <ArrowLeft class="size-4" /> Back to users
        </Link>

        <!-- Profile header -->
        <section class="flex flex-col gap-6 rounded-3xl border border-border/60 bg-card p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-5">
                <span class="flex size-20 items-center justify-center rounded-2xl bg-ink font-sans text-2xl font-semibold text-paper">
                    {{ initials(user.name) }}
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-sans text-3xl font-semibold tracking-tight">{{ user.name }}</h2>
                        <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium', roleClasses(user.role)]">
                            {{ user.role }}
                        </span>
                        <span v-if="isBanned" class="inline-flex items-center gap-1 rounded-full bg-rust/15 px-2.5 py-0.5 text-xs font-medium text-rust">
                            <ShieldBan class="size-3" /> banned
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5"><Mail class="size-3.5" /> {{ user.email }}</span>
                        <span class="inline-flex items-center gap-1.5"><Hash class="size-3.5" /> {{ user.uuid.slice(0, 12) }}</span>
                        <span>Joined {{ dateFmt(user.created_at) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="!isBanned"
                    class="inline-flex items-center gap-2 rounded-full bg-rust px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
                    @click="showBanModal = true"
                >
                    <ShieldBan class="size-4" /> Ban user
                </button>
                <button
                    v-else
                    class="inline-flex items-center gap-2 rounded-full bg-moss px-4 py-2 text-sm font-medium text-paper hover:opacity-90"
                    @click="unban"
                >
                    <UserCheck class="size-4" /> Unban
                </button>
                <button
                    class="inline-flex items-center gap-2 rounded-full border border-destructive/40 px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/5"
                    @click="deleteUser"
                >
                    <Trash2 class="size-4" /> Delete
                </button>
            </div>
        </section>

        <!-- Stats -->
        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Posts</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.posts_count ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Comments</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.comments_count ?? 0 }}</div>
            </div>
            <div class="rounded-3xl border border-border/60 bg-card p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Reports filed</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.reports_filed_count ?? 0 }}</div>
            </div>
        </section>

        <!-- Profile edit -->
        <form class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm" @submit.prevent="submitProfile">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold">Profile</h3>
                <button
                    type="submit"
                    class="inline-flex h-9 items-center gap-2 rounded-full bg-ink px-4 text-sm font-medium text-paper hover:opacity-90 disabled:opacity-40"
                    :disabled="profileForm.processing || !profileForm.isDirty"
                >
                    <Save class="size-4" /> Save
                </button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Name</label>
                    <input
                        v-model="profileForm.name"
                        type="text"
                        class="h-10 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                    />
                    <p v-if="profileForm.errors.name" class="mt-1 text-xs text-destructive">{{ profileForm.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</label>
                    <input
                        v-model="profileForm.email"
                        type="email"
                        class="h-10 w-full rounded-full bg-secondary/60 px-4 text-sm outline-none focus:bg-secondary"
                    />
                    <p v-if="profileForm.errors.email" class="mt-1 text-xs text-destructive">{{ profileForm.errors.email }}</p>
                </div>
            </div>
        </form>

        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Role assignment -->
            <div v-if="canAssignRole" class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <Crown class="size-4 text-rust" />
                    <h3 class="text-lg font-semibold">Role</h3>
                </div>
                <form class="flex items-center gap-3" @submit.prevent="submitRole">
                    <select
                        v-model="roleForm.role"
                        class="h-10 flex-1 rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                    >
                        <option value="user">User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-full bg-ink px-4 text-sm font-medium text-paper hover:opacity-90"
                        :disabled="roleForm.processing"
                    >
                        Save
                    </button>
                </form>
                <p v-if="roleForm.errors.role" class="mt-2 text-xs text-destructive">{{ roleForm.errors.role }}</p>
            </div>

            <!-- Bans history -->
            <div class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold">Ban history</h3>
                <ul v-if="user.bans?.length" class="divide-y divide-border/60">
                    <li v-for="b in user.bans" :key="b.uuid" class="py-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ b.reason }}</div>
                                <div class="text-xs text-muted-foreground">
                                    by {{ b.banned_by?.name ?? 'system' }} · {{ dateFmt(b.created_at) }}
                                    <span v-if="b.expires_at">
                                        · expires {{ dateFmt(b.expires_at) }}
                                    </span>
                                    <span v-else> · permanent</span>
                                </div>
                            </div>
                            <span
                                :class="[
                                    'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium',
                                    !b.expires_at || new Date(b.expires_at) > new Date() ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground',
                                ]"
                            >
                                {{ !b.expires_at || new Date(b.expires_at) > new Date() ? 'active' : 'lifted' }}
                            </span>
                        </div>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">
                    Never banned.
                </div>
            </div>
        </div>

        <!-- Reports against -->
        <section class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold">Reports against this user</h3>
            <ul v-if="reportsAgainst.length" class="divide-y divide-border/60">
                <li v-for="r in reportsAgainst" :key="r.uuid" class="flex items-start gap-3 py-3 text-sm">
                    <div class="flex-1">
                        <div class="font-medium">
                            <Link :href="`/admin/reports/${r.uuid}`" class="hover:text-rust">{{ r.reason }}</Link>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            filed by {{ r.reporter?.name ?? 'unknown' }} · {{ dateFmt(r.created_at) }}
                        </div>
                    </div>
                    <span
                        :class="[
                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium',
                            r.status === 'pending' ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground',
                        ]"
                    >{{ r.status }}</span>
                </li>
            </ul>
            <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">
                No reports.
            </div>
        </section>

        <!-- Activity -->
        <section class="rounded-3xl border border-border/60 bg-card p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold">Activity</h3>
            <ul v-if="activity.length" class="divide-y divide-border/60">
                <li v-for="a in activity" :key="a.uuid" class="flex items-start justify-between gap-3 py-3 text-sm">
                    <div>
                        <div class="font-medium">{{ a.action }}</div>
                        <div class="text-xs text-muted-foreground">by {{ a.admin?.name ?? 'system' }}</div>
                    </div>
                    <span class="shrink-0 text-[11px] text-muted-foreground">{{ dateFmt(a.created_at) }}</span>
                </li>
            </ul>
            <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">
                No activity recorded.
            </div>
        </section>

        <!-- Ban modal -->
        <Teleport to="body">
            <div v-if="showBanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">Ban {{ user.name }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Their sessions and tokens will be revoked.</p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitBan">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Reason</label>
                            <textarea
                                v-model="banForm.reason"
                                rows="3"
                                class="w-full rounded-2xl bg-secondary/60 p-3 text-sm outline-none focus:bg-secondary"
                                placeholder="Repeated harassment..."
                                required
                            />
                            <p v-if="banForm.errors.reason" class="mt-1 text-xs text-destructive">{{ banForm.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Expires at (optional)</label>
                            <input
                                v-model="banForm.expires_at"
                                type="datetime-local"
                                class="w-full rounded-full bg-secondary/60 px-4 py-2 text-sm outline-none focus:bg-secondary"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Leave empty for a permanent ban.</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="rounded-full px-4 py-2 text-sm hover:bg-secondary" @click="showBanModal = false">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-full bg-rust px-4 py-2 text-sm font-medium text-paper hover:opacity-90" :disabled="banForm.processing">
                                Confirm ban
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
