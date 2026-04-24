<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Filter, MoreHorizontal, Search, ShieldBan, UserCheck, UserX } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters?.filter?.search ?? '');
const roleFilter = ref(props.filters?.filter?.role ?? '');
const bannedOnly = ref(!!props.filters?.filter?.banned);

const applyFilters = () => {
    router.get(
        '/admin/users',
        {
            filter: {
                ...(search.value ? { search: search.value } : {}),
                ...(roleFilter.value ? { role: roleFilter.value } : {}),
                ...(bannedOnly.value ? { banned: 1 } : {}),
            },
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const roleClasses = (role) => {
    switch (role) {
        case 'super_admin': return 'bg-rust/15 text-rust';
        case 'admin': return 'bg-ink/10 text-ink';
        case 'moderator': return 'bg-moss/15 text-moss';
        default: return 'bg-secondary text-muted-foreground';
    }
};

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const isBanned = (u) => (u.bans ?? []).length > 0;

const openRow = ref(null);
const toggleRow = (uuid) => (openRow.value = openRow.value === uuid ? null : uuid);

const quickBan = (user) => {
    const reason = window.prompt(`Ban ${user.name}? Enter reason:`);
    if (!reason) return;
    router.post(`/admin/users/${user.uuid}/ban`, { reason }, { preserveScroll: true });
};
const quickUnban = (user) => router.post(`/admin/users/${user.uuid}/unban`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Users" />
    <AdminLayout title="Users">
        <div v-if="page.props.flash?.status" class="rounded-2xl bg-moss/10 px-4 py-3 text-sm text-moss">
            {{ page.props.flash.status }}
        </div>

        <!-- Toolbar -->
        <div class="rounded-3xl border border-border/60 bg-card p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name or email..."
                        class="h-10 w-full rounded-full bg-secondary/60 pl-10 pr-4 text-sm outline-none placeholder:text-muted-foreground focus:bg-secondary"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <div class="relative">
                    <select
                        v-model="roleFilter"
                        class="h-10 appearance-none rounded-full bg-secondary/60 pl-4 pr-9 text-sm outline-none hover:bg-secondary"
                        @change="applyFilters"
                    >
                        <option value="">All roles</option>
                        <option value="user">User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                </div>
                <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full bg-secondary/60 px-4 text-sm hover:bg-secondary">
                    <input v-model="bannedOnly" type="checkbox" class="accent-rust" @change="applyFilters" />
                    Banned only
                </label>
                <button
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-ink px-4 text-sm font-medium text-paper hover:opacity-90"
                    @click="applyFilters"
                >
                    <Filter class="size-4" /> Apply
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm">
            <div class="grid grid-cols-[1.8fr_1fr_0.8fr_0.6fr_0.8fr_2.5rem] items-center gap-4 border-b border-border/60 px-6 py-3 text-[11px] uppercase tracking-wide text-muted-foreground">
                <span>User</span>
                <span>Email</span>
                <span>Role</span>
                <span>Posts</span>
                <span>Joined</span>
                <span></span>
            </div>

            <ul class="divide-y divide-border/60">
                <li
                    v-for="u in users.data"
                    :key="u.uuid"
                    class="grid grid-cols-[1.8fr_1fr_0.8fr_0.6fr_0.8fr_2.5rem] items-center gap-4 px-6 py-3 text-sm"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-paper">
                            {{ initials(u.name) }}
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Link :href="`/admin/users/${u.uuid}`" class="truncate font-medium hover:text-rust">{{ u.name }}</Link>
                                <span v-if="isBanned(u)" class="inline-flex items-center gap-1 rounded-full bg-rust/15 px-2 py-0.5 text-[10px] font-medium text-rust">
                                    <ShieldBan class="size-3" /> banned
                                </span>
                            </div>
                            <div class="truncate text-[11px] text-muted-foreground">#{{ u.uuid.slice(0, 8) }}</div>
                        </div>
                    </div>
                    <div class="truncate text-muted-foreground">{{ u.email }}</div>
                    <div>
                        <span :class="['inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium', roleClasses(u.role)]">
                            {{ u.role }}
                        </span>
                    </div>
                    <div class="text-muted-foreground">{{ u.posts_count ?? 0 }}</div>
                    <div class="text-muted-foreground">{{ dateFmt(u.created_at) }}</div>
                    <div class="relative flex justify-end">
                        <button
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground hover:bg-secondary hover:text-ink"
                            @click="toggleRow(u.uuid)"
                        >
                            <MoreHorizontal class="size-4" />
                        </button>
                        <div
                            v-if="openRow === u.uuid"
                            class="absolute right-0 top-10 z-10 w-44 overflow-hidden rounded-2xl border border-border/60 bg-popover shadow-lg"
                        >
                            <Link :href="`/admin/users/${u.uuid}`" class="block px-4 py-2 text-sm hover:bg-secondary">View profile</Link>
                            <button
                                v-if="!isBanned(u)"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rust hover:bg-secondary"
                                @click="quickBan(u); openRow = null"
                            >
                                <ShieldBan class="size-4" /> Ban user
                            </button>
                            <button
                                v-else
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-moss hover:bg-secondary"
                                @click="quickUnban(u); openRow = null"
                            >
                                <UserCheck class="size-4" /> Unban
                            </button>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-if="!users.data.length" class="py-16 text-center text-sm text-muted-foreground">
                No users match these filters.
            </div>

            <!-- Pagination -->
            <div v-if="users.data.length" class="flex items-center justify-between border-t border-border/60 px-6 py-4 text-xs text-muted-foreground">
                <span>Showing {{ users.from }}–{{ users.to }} of {{ users.total }}</span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in users.links"
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
    </AdminLayout>
</template>
