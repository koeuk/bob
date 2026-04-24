<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Bookmark,
    ChevronDown,
    Compass,
    HelpCircle,
    Info,
    LayoutGrid,
    LogOut,
    Mail,
    MessageSquare,
    Search,
    Settings as SettingsIcon,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({ breadcrumbs: { type: Array, default: () => [] } });

const page = usePage();

const user = computed(() => page.props.auth?.user);
const initials = computed(() => {
    const name = user.value?.name ?? '';
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || 'U';
});
const isModerator = computed(() => ['moderator', 'admin', 'super_admin'].includes(user.value?.role));

const pillNav = [
    { href: '/dashboard', label: 'Overview' },
    { href: '/feed', label: 'Feed' },
    { href: '/friends', label: 'People' },
    { href: '/pages', label: 'Pages' },
    { href: '/bookmarks', label: 'Bookmarks' },
    { href: '/reports', label: 'Reports' },
];

const railNav = computed(() => [
    { href: '/dashboard', label: 'Home', icon: Compass },
    { href: '/feed', label: 'Feed', icon: LayoutGrid },
    { href: '/messages', label: 'Messages', icon: Mail },
    { href: '/notifications', label: 'Notices', icon: MessageSquare },
    { href: '/friends', label: 'People', icon: Users },
    { href: '/bookmarks', label: 'Bookmarks', icon: Bookmark },
    { href: '/settings/profile', label: 'Settings', icon: SettingsIcon },
    ...(isModerator.value ? [{ href: '/admin/dashboard', label: 'Admin', icon: ShieldCheck }] : []),
]);

const isActive = (href) => page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="sticky top-0 z-30 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <Link href="/dashboard" class="flex items-center gap-2.5">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-rust text-paper shadow-sm">
                        <span class="font-serif text-xl leading-none">b</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-1 rounded-full border border-border/60 bg-card/70 p-1 shadow-sm backdrop-blur md:flex">
                    <Link
                        v-for="item in pillNav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-ink text-paper shadow'
                                : 'text-muted-foreground hover:text-ink',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <button class="inline-flex size-10 items-center justify-center rounded-full border border-border/60 bg-card/70 text-muted-foreground hover:text-ink">
                        <Search class="size-4" />
                    </button>
                    <button class="relative inline-flex size-10 items-center justify-center rounded-full border border-border/60 bg-card/70 text-muted-foreground hover:text-ink">
                        <Bell class="size-4" />
                        <span class="absolute right-2 top-2 size-1.5 rounded-full bg-rust"></span>
                    </button>
                    <button class="inline-flex size-10 items-center justify-center rounded-full border border-border/60 bg-card/70 text-muted-foreground hover:text-ink">
                        <Info class="size-4" />
                    </button>
                    <AppearanceDropdown />
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center gap-2 rounded-full border border-border/60 bg-card/70 py-1 pl-1 pr-3 shadow-sm hover:text-rust transition-colors">
                            <span class="flex size-8 items-center justify-center rounded-full bg-ink text-paper text-xs font-semibold">
                                {{ initials }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block text-sm font-medium leading-tight">{{ user?.name }}</span>
                                <span class="block text-[11px] leading-tight text-muted-foreground">{{ user?.email }}</span>
                            </span>
                            <ChevronDown class="hidden size-3.5 text-muted-foreground sm:block" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-[1400px] gap-4 px-4 pb-10 sm:px-6">
            <aside class="sticky top-24 hidden h-[calc(100vh-7rem)] shrink-0 flex-col items-center justify-between rounded-3xl border border-border/60 bg-card/70 py-4 shadow-sm backdrop-blur md:flex">
                <nav class="flex flex-col items-center gap-1.5 px-2">
                    <Link
                        v-for="item in railNav"
                        :key="item.href"
                        :href="item.href"
                        :title="item.label"
                        :class="[
                            'group relative inline-flex size-11 items-center justify-center rounded-2xl transition-colors',
                            isActive(item.href)
                                ? 'bg-ink text-paper shadow'
                                : 'text-muted-foreground hover:bg-secondary hover:text-ink',
                        ]"
                    >
                        <component :is="item.icon" class="size-[18px]" />
                    </Link>
                </nav>
                <div class="flex flex-col items-center gap-1.5 px-2">
                    <button class="inline-flex size-11 items-center justify-center rounded-2xl text-muted-foreground hover:bg-secondary hover:text-ink">
                        <HelpCircle class="size-[18px]" />
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="inline-flex size-11 items-center justify-center rounded-2xl text-muted-foreground hover:bg-secondary hover:text-ink"
                    >
                        <LogOut class="size-[18px]" />
                    </Link>
                </div>
            </aside>

            <main class="min-w-0 flex-1 space-y-6">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="-translate-y-1 opacity-0"
                >
                    <div v-if="page.props.flash?.status" class="rounded-2xl bg-moss/10 px-4 py-3 text-sm text-moss">
                        {{ page.props.flash.status }}
                    </div>
                </Transition>
                <slot />
            </main>
        </div>
    </div>
</template>
