<template>
    <Head title="Posts" />
    <AdminLayout title="Posts">
        <div class="flex justify-end">
            <Button as-child class="rounded-full bg-moss text-paper hover:bg-moss/90">
                <Link href="/admin/posts/create">
                    <Plus class="size-4" /> New post
                </Link>
            </Button>
        </div>

        <Card class="rounded-3xl border-white gap-0 p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search body..."
                        class="h-10 w-full rounded-full bg-secondary/60 border-white shadow-none focus-visible:ring-0 focus-visible:bg-secondary pl-10"
                        @keydown.enter="apply"
                    />
                </div>
                <Popover v-model:open="openStatus">
                    <PopoverTrigger as-child>
                        <button
                            type="button"
                            role="combobox"
                            :aria-expanded="openStatus"
                            class="h-10 inline-flex items-center gap-2 rounded-full bg-secondary/60 px-4 text-sm outline-none hover:bg-secondary"
                        >
                            {{ statusLabel }}
                            <ChevronsUpDown class="size-3.5 text-muted-foreground" />
                        </button>
                    </PopoverTrigger>
                    <PopoverContent class="w-44 p-0">
                        <Command>
                            <CommandList>
                                <CommandGroup>
                                    <CommandItem
                                        v-for="o in statusOptions"
                                        :key="o.value"
                                        :value="o.value"
                                        @select="(ev) => { status = ev.detail.value; openStatus = false; apply() }"
                                    >
                                        <Check :class="cn('mr-2 size-4', status === o.value ? 'opacity-100' : 'opacity-0')" />
                                        {{ o.label }}
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
            </div>
        </Card>

        <div class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="p in posts.data"
                :key="p.uuid"
                :href="`/admin/posts/${p.uuid}`"
                class="group flex flex-col gap-3 rounded-3xl border-2 border-white dark:border-white/10 shadow-sm bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-full bg-forest text-[11px] font-semibold text-paper">
                            {{ initials(p.user?.name) }}
                        </span>
                        <div>
                            <div class="text-sm font-medium">{{ p.user?.name ?? 'unknown' }}</div>
                            <div class="text-[11px] text-muted-foreground">{{ dateFmt(p.created_at) }}</div>
                        </div>
                    </div>
                    <Badge :class="['rounded-full border-0', statusTone(p.status)]">{{ p.status }}</Badge>
                </div>
                <p class="text-sm leading-relaxed text-muted-foreground group-hover:text-moss">
                    {{ truncate(p.body) }}
                </p>
                <div class="flex items-center gap-5 pt-1 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1"><Heart class="size-3.5" /> {{ p.likes_count }}</span>
                    <span class="inline-flex items-center gap-1"><MessageCircle class="size-3.5" /> {{ p.comments_count }}</span>
                    <span v-if="p.reports_count > 0" class="inline-flex items-center gap-1 text-rust">
                        <Flag class="size-3.5" /> {{ p.reports_count }}
                    </span>
                </div>
            </Link>
        </div>

        <Card v-if="!posts.data.length" class="rounded-3xl border-white gap-0 p-6">
            <div class="py-10 text-center text-sm text-muted-foreground">No posts match.</div>
        </Card>

        <div v-if="posts.data.length" class="flex items-center justify-between text-xs text-muted-foreground">
            <span>Showing {{ posts.from }}–{{ posts.to }} of {{ posts.total }}</span>
            <div class="flex items-center gap-1">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs',
                        link.active ? 'bg-moss text-paper' : link.url ? 'hover:bg-secondary' : 'opacity-40',
                    ]"
                    preserve-scroll
                    preserve-state
                />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/admin-layout.vue';
import Card from '@/components/ui/Card.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/Input.vue';
import Badge from '@/components/ui/Badge.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandGroup, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Flag, Heart, MessageCircle, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters?.filter?.search ?? '');
const status = ref(props.filters?.filter?.status ?? '');

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'flagged', label: 'Flagged' },
    { value: 'hidden', label: 'Hidden' },
];
const openStatus = ref(false);
const statusLabel = computed(() => statusOptions.find(o => o.value === status.value)?.label ?? 'All statuses');

const apply = () => router.get('/admin/posts', {
    filter: {
        ...(search.value ? { search: search.value } : {}),
        ...(status.value ? { status: status.value } : {}),
    },
}, { preserveState: true, preserveScroll: true, replace: true });

const dateFmt = (iso) => new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const statusTone = (s) => {
    switch (s) {
        case 'flagged': return 'bg-rust/15 text-rust';
        case 'hidden': return 'bg-ink/10 text-ink';
        default: return 'bg-moss/15 text-moss';
    }
};

const truncate = (t, n = 160) => (t ?? '').length > n ? (t ?? '').slice(0, n) + '…' : t;
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
</script>
