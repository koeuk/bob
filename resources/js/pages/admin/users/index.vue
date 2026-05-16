<template>
    <Head title="Users" />

    <!-- Ban confirmation dialog -->
    <Dialog :open="!!banTarget" @update:open="(v) => { if (!v) banTarget = null; banReason = '' }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Ban {{ banTarget?.name }}?</DialogTitle>
                <DialogDescription>
                    This will revoke their access immediately. You must provide a reason.
                </DialogDescription>
            </DialogHeader>
            <div class="pt-1">
                <Textarea
                    v-model="banReason"
                    placeholder="Reason for ban…"
                    rows="3"
                    class="rounded-2xl bg-secondary/60 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary resize-none"
                />
            </div>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="banTarget = null; banReason = ''">
                    Cancel
                </Button>
                <Button
                    :disabled="!banReason.trim()"
                    class="rounded-full bg-rust text-paper hover:bg-rust/90"
                    @click="confirmBan"
                >
                    <ShieldBan class="size-4" /> Ban user
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete confirmation dialog -->
    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Delete user?</DialogTitle>
                <DialogDescription>
                    This will permanently delete <strong>{{ deleteTarget?.name }}</strong> and all their data. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="deleteTarget = null">
                    Cancel
                </Button>
                <Button
                    class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="confirmDelete"
                >
                    <Trash2 class="size-4" /> Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <AdminLayout title="Users">
        <div class="flex justify-end">
            <Button as-child class="rounded-full bg-ink text-paper hover:bg-ink/90">
                <Link href="/admin/users/create">
                    <Plus class="size-4" /> New user
                </Link>
            </Button>
        </div>

        <!-- Toolbar -->
        <Card class="rounded-3xl border-border/60 gap-0 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search name or email..."
                        class="h-10 rounded-full bg-secondary/60 pl-10 border-0 shadow-none focus-visible:ring-0 focus-visible:bg-secondary"
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
                <Button class="rounded-full bg-ink text-paper hover:bg-ink/90" @click="applyFilters">
                    <Filter class="size-4" /> Apply
                </Button>
            </div>
        </Card>

        <!-- Table -->
        <Card class="rounded-3xl border-border/60 gap-0 overflow-hidden">
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
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-paper overflow-hidden">
                            <img v-if="u.avatar" :src="`/storage/${u.avatar}`" :alt="u.name" class="size-9 object-cover" />
                            <template v-else>{{ initials(u.name) }}</template>
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <Link :href="`/admin/users/${u.uuid}`" class="truncate font-medium hover:text-rust">{{ u.name }}</Link>
                                <Badge v-if="isBanned(u)" class="rounded-full border-0 bg-rust/15 text-rust inline-flex items-center gap-1">
                                    <ShieldBan class="size-3" /> banned
                                </Badge>
                            </div>
                            <div class="truncate text-[11px] text-muted-foreground">#{{ u.uuid.slice(0, 8) }}</div>
                        </div>
                    </div>
                    <div class="truncate text-muted-foreground">{{ u.email }}</div>
                    <div>
                        <Badge :class="['rounded-full border-0', roleClasses(u.role)]">
                            {{ u.role }}
                        </Badge>
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
                            <Link :href="`/admin/users/${u.uuid}/edit`" class="block px-4 py-2 text-sm hover:bg-secondary">Edit user</Link>
                            <button
                                v-if="!isBanned(u)"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rust hover:bg-secondary"
                                @click="banTarget = u; openRow = null"
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
                            <div class="border-t border-border/60 my-1" />
                            <button
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-destructive hover:bg-secondary"
                                @click="deleteTarget = u; openRow = null"
                            >
                                <Trash2 class="size-4" /> Delete user
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
        </Card>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Badge from '@/components/ui/Badge.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Filter, MoreHorizontal, Plus, Search, ShieldBan, Trash2, UserCheck, UserX } from 'lucide-vue-next';
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

const deleteTarget = ref(null);
const confirmDelete = () => {
    if (!deleteTarget.value) return;
    router.delete(`/admin/users/${deleteTarget.value.uuid}`, { preserveScroll: true });
    deleteTarget.value = null;
};

const banTarget = ref(null);
const banReason = ref('');
const confirmBan = () => {
    if (!banTarget.value || !banReason.value.trim()) return;
    router.post(`/admin/users/${banTarget.value.uuid}/ban`, { reason: banReason.value.trim() }, { preserveScroll: true });
    banTarget.value = null;
    banReason.value = '';
};

const quickUnban = (user) => router.post(`/admin/users/${user.uuid}/unban`, {}, { preserveScroll: true });
</script>
