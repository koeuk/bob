<template>
    <Head :title="user.name" />

    <Dialog :open="showDeleteDialog" @update:open="(v) => { showDeleteDialog = v }">
        <DialogContent class="max-w-sm rounded-3xl">
            <DialogHeader>
                <DialogTitle>Delete user?</DialogTitle>
                <DialogDescription>
                    This will permanently delete <strong>{{ user.name }}</strong> and all their data. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-row justify-end gap-2 pt-2">
                <Button variant="outline" class="rounded-full" @click="showDeleteDialog = false">
                    Cancel
                </Button>
                <Button class="rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="confirmDelete">
                    <Trash2 class="size-4" /> Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <AdminLayout>
        <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-moss transition-colors">
            <ArrowLeft class="size-4" /> Back to users
        </Link>

        <!-- Profile header -->
        <Card class="rounded-3xl border-white gap-0 p-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-5">
                    <div class="relative size-20 shrink-0">
                        <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="size-20 rounded-2xl object-cover" />
                        <span v-else class="flex size-20 items-center justify-center rounded-2xl bg-forest font-sans text-2xl font-semibold text-paper">
                            {{ initials(user.name) }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-sans text-3xl font-semibold tracking-tight">{{ user.name }}</h2>
                            <Badge :class="['rounded-full border-0', roleClasses(user.role)]">{{ user.role }}</Badge>
                            <Badge v-if="isBanned" class="rounded-full border-0 bg-rust/15 text-rust inline-flex items-center gap-1">
                                <ShieldBan class="size-3" /> banned
                            </Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5"><Mail class="size-3.5" /> {{ user.email }}</span>
                            <span class="inline-flex items-center gap-1.5"><Hash class="size-3.5" /> {{ user.uuid.slice(0, 12) }}</span>
                            <span>Joined {{ dateFmt(user.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button as-child class="rounded-full bg-moss text-paper hover:bg-moss/90">
                        <Link :href="`/admin/users/${user.uuid}/edit`">
                            <Pencil class="size-4" /> Edit
                        </Link>
                    </Button>
                    <Button
                        v-if="!isBanned"
                        class="rounded-full bg-rust text-paper hover:bg-rust/90"
                        @click="showBanModal = true"
                    >
                        <ShieldBan class="size-4" /> Ban user
                    </Button>
                    <Button
                        v-else
                        class="rounded-full bg-moss text-paper hover:bg-moss/90"
                        @click="unban"
                    >
                        <UserCheck class="size-4" /> Unban
                    </Button>
                    <Button
                        variant="outline"
                        class="rounded-full border-destructive/40 text-destructive hover:bg-destructive/5"
                        @click="deleteUser"
                    >
                        <Trash2 class="size-4" /> Delete
                    </Button>
                </div>
            </div>
        </Card>

        <!-- Stats -->
        <section class="grid gap-4 sm:grid-cols-3">
            <Card class="rounded-3xl border-white gap-0 p-5">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Posts</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.posts_count ?? 0 }}</div>
            </Card>
            <Card class="rounded-3xl border-white gap-0 p-5">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Comments</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.comments_count ?? 0 }}</div>
            </Card>
            <Card class="rounded-3xl border-white gap-0 p-5">
                <div class="text-xs uppercase tracking-wide text-muted-foreground">Reports filed</div>
                <div class="mt-2 font-sans text-3xl font-semibold tracking-tight">{{ user.reports_filed_count ?? 0 }}</div>
            </Card>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Ban history -->
            <Card class="rounded-3xl border-white gap-0">
                <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Ban history</CardTitle></CardHeader>
                <CardContent class="px-6 pb-6 pt-4">
                    <ul v-if="user.bans?.length" class="divide-y divide-border/60">
                        <li v-for="b in user.bans" :key="b.uuid" class="py-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ b.reason }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        by {{ b.banned_by?.name ?? 'system' }} · {{ dateFmt(b.created_at) }}
                                        <span v-if="b.expires_at"> · expires {{ dateFmt(b.expires_at) }}</span>
                                        <span v-else> · permanent</span>
                                    </div>
                                </div>
                                <Badge :class="['shrink-0 rounded-full border-0', !b.expires_at || new Date(b.expires_at) > new Date() ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground']">
                                    {{ !b.expires_at || new Date(b.expires_at) > new Date() ? 'active' : 'lifted' }}
                                </Badge>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">Never banned.</div>
                </CardContent>
            </Card>

            <!-- Reports against -->
            <Card class="rounded-3xl border-white gap-0">
                <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Reports against this user</CardTitle></CardHeader>
                <CardContent class="px-6 pb-6 pt-4">
                    <ul v-if="reportsAgainst.length" class="divide-y divide-border/60">
                        <li v-for="r in reportsAgainst" :key="r.uuid" class="flex items-start gap-3 py-3 text-sm">
                            <div class="flex-1">
                                <div class="font-medium">
                                    <Link :href="`/admin/reports/${r.uuid}`" class="hover:text-moss transition-colors">{{ r.reason }}</Link>
                                </div>
                                <div class="text-xs text-muted-foreground">filed by {{ r.reporter?.name ?? 'unknown' }} · {{ dateFmt(r.created_at) }}</div>
                            </div>
                            <Badge :class="['shrink-0 rounded-full border-0', r.status === 'pending' ? 'bg-rust/15 text-rust' : 'bg-secondary text-muted-foreground']">{{ r.status }}</Badge>
                        </li>
                    </ul>
                    <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">No reports.</div>
                </CardContent>
            </Card>
        </div>

        <!-- Activity -->
        <Card class="rounded-3xl border-white gap-0">
            <CardHeader class="px-6 pt-6 pb-0"><CardTitle>Activity</CardTitle></CardHeader>
            <CardContent class="px-6 pb-6 pt-4">
                <ul v-if="activity.length" class="divide-y divide-border/60">
                    <li v-for="a in activity" :key="a.uuid" class="flex items-start justify-between gap-3 py-3 text-sm">
                        <div>
                            <div class="font-medium">{{ a.action }}</div>
                            <div class="text-xs text-muted-foreground">by {{ a.admin?.name ?? 'system' }}</div>
                        </div>
                        <span class="shrink-0 text-[11px] text-muted-foreground">{{ dateFmt(a.created_at) }}</span>
                    </li>
                </ul>
                <div v-else class="rounded-2xl bg-secondary/40 py-8 text-center text-sm text-muted-foreground">No activity recorded.</div>
            </CardContent>
        </Card>

        <!-- Ban modal -->
        <Teleport to="body">
            <div v-if="showBanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl">
                    <h3 class="text-xl font-semibold">Ban {{ user.name }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Their sessions and tokens will be revoked.</p>
                    <form class="mt-5 space-y-4" @submit.prevent="submitBan">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Reason</label>
                            <Textarea v-model="banForm.reason" rows="3" class="rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" placeholder="Repeated harassment..." required />
                            <p v-if="banForm.errors.reason" class="mt-1 text-xs text-destructive">{{ banForm.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-muted-foreground">Expires at (optional)</label>
                            <Input v-model="banForm.expires_at" type="datetime-local" class="h-11 rounded-2xl bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary" />
                            <p class="mt-1 text-xs text-muted-foreground">Leave empty for a permanent ban.</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" class="rounded-full" @click="showBanModal = false">Cancel</Button>
                            <Button type="submit" class="rounded-full bg-rust text-paper hover:bg-rust/90" :disabled="banForm.processing">Confirm ban</Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import Badge from '@/components/ui/Badge.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Hash, Mail, Pencil, ShieldBan, Trash2, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    reportsAgainst: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
});

const activeBans = computed(() => (props.user.bans ?? []).filter((b) => !b.expires_at || new Date(b.expires_at) > new Date()));
const isBanned = computed(() => activeBans.value.length > 0);

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
const dateFmt = (iso) => iso ? new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const roleClasses = (role) => {
    switch (role) {
        case 'super_admin': return 'bg-rust/15 text-rust';
        case 'admin':       return 'bg-ink/10 text-ink';
        case 'moderator':   return 'bg-moss/15 text-moss';
        default:            return 'bg-secondary text-muted-foreground';
    }
};

// Ban
const showBanModal = ref(false);
const banForm = useForm({ reason: '', expires_at: '' });
const submitBan = () => banForm.post(`/admin/users/${props.user.uuid}/ban`, {
    preserveScroll: true,
    onSuccess: () => { showBanModal.value = false; banForm.reset(); },
});
const unban = () => router.post(`/admin/users/${props.user.uuid}/unban`, {}, { preserveScroll: true });

// Delete
const showDeleteDialog = ref(false);
const deleteUser = () => { showDeleteDialog.value = true; };
const confirmDelete = () => {
    showDeleteDialog.value = false;
    router.delete(`/admin/users/${props.user.uuid}`);
};
</script>
