<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ActivitySquare,
    Bell,
    ChevronDown,
    FileText,
    Flag,
    Gauge,
    HelpCircle,
    LogOut,
    MessageSquare,
    Newspaper,
    Search,
    Settings as SettingsIcon,
    ShieldBan,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({ title: { type: String, default: '' } });

const page = usePage();

const user = computed(() => page.props.auth?.user);
const initials = computed(() => {
    const name = user.value?.name ?? '';
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || 'A';
});
const isSuperOrAdmin = computed(() => ['admin', 'super_admin'].includes(user.value?.role));

const primaryNav = computed(() => [
    { href: '/admin/dashboard', label: 'Overview' },
    { href: '/admin/activity-logs', label: 'Activity' },
    { href: '/admin/users', label: 'Manage' },
    { href: '/admin/reports', label: 'Reports' },
    { href: '/admin/posts', label: 'Content' },
    ...(isSuperOrAdmin.value ? [{ href: '/admin/settings', label: 'Settings' }] : []),
]);

const sideNav = computed(() => [
    { href: '/admin/dashboard', label: 'Dashboard', icon: Gauge },
    { href: '/admin/users', label: 'Users', icon: Users },
    { href: '/admin/reports', label: 'Reports', icon: Flag },
    { href: '/admin/bans', label: 'Bans', icon: ShieldBan },
    { href: '/admin/posts', label: 'Posts', icon: Newspaper },
    { href: '/admin/comments', label: 'Comments', icon: MessageSquare },
    { href: '/admin/pages', label: 'Pages', icon: FileText },
    ...(isSuperOrAdmin.value ? [{ href: '/admin/settings', label: 'Settings', icon: SettingsIcon }] : []),
    { href: '/admin/activity-logs', label: 'Activity', icon: ActivitySquare },
]);

const isActive = (href) => page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Top bar -->
        <header class="sticky top-0 z-30 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <!-- Logo -->
                <Link href="/admin/dashboard" class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-2xl bg-rust text-paper shadow-sm">
                        <span class="font-serif text-lg leading-none">b</span>
                    </span>
                    <span class="font-sans text-lg font-semibold tracking-tight">bob/admin</span>
                </Link>

                <!-- Pill nav -->
                <nav class="hidden items-center gap-1 rounded-full border border-border/60 bg-card/70 p-1 shadow-sm backdrop-blur md:flex">
                    <Link
                        v-for="item in primaryNav"
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

                <!-- Right controls -->
                <div class="flex items-center gap-2">
                    <button class="inline-flex size-10 items-center justify-center rounded-full border border-border/60 bg-card/70 text-muted-foreground hover:text-ink">
                        <Search class="size-4" />
                    </button>
                    <button class="relative inline-flex size-10 items-center justify-center rounded-full border border-border/60 bg-card/70 text-muted-foreground hover:text-ink">
                        <Bell class="size-4" />
                        <span class="absolute right-2 top-2 size-1.5 rounded-full bg-rust"></span>
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

        <!-- Body: icon sidebar + content -->
        <div class="mx-auto flex max-w-[1400px] gap-4 px-4 pb-10 sm:px-6">
            <!-- Icon rail -->
            <aside class="sticky top-24 hidden h-[calc(100vh-7rem)] shrink-0 flex-col items-center justify-between rounded-3xl border border-border/60 bg-card/70 py-4 shadow-sm backdrop-blur md:flex">
                <nav class="flex flex-col items-center gap-1.5 px-2">
                    <Link
                        v-for="item in sideNav"
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
                        href="/dashboard"
                        title="Back to reader"
                        class="inline-flex size-11 items-center justify-center rounded-2xl text-muted-foreground hover:bg-secondary hover:text-ink"
                    >
                        <LogOut class="size-[18px]" />
                    </Link>
                </div>
            </aside>

            <!-- Content -->
            <main class="min-w-0 flex-1 space-y-6">
                <div v-if="title" class="flex items-end justify-between gap-4 pt-2">
                    <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">{{ title }}</h1>
                </div>
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
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                >
                    <div v-if="page.props.flash?.error" class="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {{ page.props.flash.error }}
                    </div>
                </Transition>
                <slot />
            </main>
        </div>
    </div>
</template>
