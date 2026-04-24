<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ShieldBan, Undo2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    bans: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
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
</script>

<template>
    <Head title="Bans" />
    <AdminLayout title="Bans">
        <div v-if="page.props.flash?.status" class="rounded-2xl bg-moss/10 px-4 py-3 text-sm text-moss">
            {{ page.props.flash.status }}
        </div>

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
            <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full bg-card px-4 text-sm shadow-sm hover:bg-secondary">
                <input :checked="activeOnly" type="checkbox" class="accent-rust" @change="toggleActive" />
                Active only
            </label>
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
    </AdminLayout>
</template>
