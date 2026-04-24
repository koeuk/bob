<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import Breadcrumbs from '@/components/breadcrumbs.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { useInitials } from '@/composables/useInitials';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell, BookmarkPlus, ChevronDown, Cog, HelpCircle, Home, Info,
    LayoutGrid, LogOut, Mail, MessageSquare, Search, Users,
} from 'lucide-vue-next';

defineProps({ breadcrumbs: { type: Array, default: () => [] } });

const page = usePage();
const getInitials = useInitials();

const tabs = [
    { href: '/dashboard', label: 'Overview' },
    { href: '/feed', label: 'Feed' },
    { href: '/people', label: 'People' },
    { href: '/pages', label: 'Pages' },
    { href: '/bookmarks', label: 'Bookmarks' },
    { href: '/reports', label: 'Reports' },
];

const rail = [
    { href: '/dashboard', icon: Home, label: 'Home' },
    { href: '/feed', icon: LayoutGrid, label: 'Feed' },
    { href: '/messages', icon: Mail, label: 'Messages' },
    { href: '/notifications', icon: MessageSquare, label: 'Notifications' },
    { href: '/people', icon: Users, label: 'People' },
    { href: '/bookmarks', icon: BookmarkPlus, label: 'Saved' },
    { href: '/settings/profile', icon: Cog, label: 'Settings' },
];

const isActive = (href) =>
    page.url === href || (href !== '/' && page.url.startsWith(href));
</script>

<template>
    <div class="min-h-screen bg-muted/40 text-foreground">
        <div class="flex min-h-screen gap-4 p-4 sm:gap-5 sm:p-5">
            <!-- Left icon rail -->
            <aside class="sticky top-5 hidden h-[calc(100vh-2.5rem)] w-14 shrink-0 flex-col items-center justify-between rounded-[28px] bg-card shadow-sm lg:flex">
                <div class="flex flex-col items-center gap-2 py-5">
                    <Link href="/dashboard" class="mb-2 flex size-10 items-center justify-center rounded-full bg-accent text-accent-foreground shadow-sm shadow-accent/30">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 20V4h8a4 4 0 0 1 1.7 7.6A4.5 4.5 0 0 1 15 20H7Z" />
                        </svg>
                    </Link>

                    <Link
                        v-for="item in rail"
                        :key="item.href"
                        :href="item.href"
                        :title="item.label"
                        :class="[
                            'group relative flex size-10 items-center justify-center rounded-xl transition',
                            isActive(item.href)
                                ? 'bg-foreground text-background'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="size-[18px]" />
                    </Link>
                </div>

                <div class="flex flex-col items-center gap-2 py-5">
                    <button class="flex size-10 items-center justify-center rounded-xl text-muted-foreground hover:bg-muted hover:text-foreground transition">
                        <HelpCircle class="size-[18px]" />
                    </button>
                    <button class="flex size-10 items-center justify-center rounded-xl text-muted-foreground hover:bg-muted hover:text-foreground transition">
                        <LogOut class="size-[18px]" />
                    </button>
                </div>
            </aside>

            <!-- Main column -->
            <div class="flex min-w-0 flex-1 flex-col gap-5">
                <!-- Top bar -->
                <header class="flex h-16 items-center gap-4 rounded-[28px] bg-card px-4 shadow-sm">
                    <!-- Mobile wordmark -->
                    <Link href="/dashboard" class="flex items-center gap-2 pl-2 lg:hidden">
                        <span class="flex size-8 items-center justify-center rounded-full bg-accent text-accent-foreground">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M7 20V4h8a4 4 0 0 1 1.7 7.6A4.5 4.5 0 0 1 15 20H7Z" />
                            </svg>
                        </span>
                        <span class="text-lg font-semibold tracking-tight">bob</span>
                    </Link>

                    <!-- Pill tabs (desktop) -->
                    <nav class="mx-auto hidden items-center gap-1 rounded-full bg-muted/60 p-1 md:flex">
                        <Link
                            v-for="t in tabs"
                            :key="t.href"
                            :href="t.href"
                            :class="[
                                'rounded-full px-4 py-1.5 text-sm font-medium transition',
                                isActive(t.href)
                                    ? 'bg-foreground text-background shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            {{ t.label }}
                        </Link>
                    </nav>

                    <div class="ml-auto flex items-center gap-1.5">
                        <button class="hidden size-10 items-center justify-center rounded-full hover:bg-muted transition sm:inline-flex">
                            <Search class="size-[18px] text-muted-foreground" />
                        </button>
                        <button class="relative hidden size-10 items-center justify-center rounded-full hover:bg-muted transition sm:inline-flex">
                            <Bell class="size-[18px] text-muted-foreground" />
                            <span class="absolute right-2.5 top-2.5 size-2 rounded-full bg-accent ring-2 ring-card"></span>
                        </button>
                        <button class="hidden size-10 items-center justify-center rounded-full hover:bg-muted transition sm:inline-flex">
                            <Info class="size-[18px] text-muted-foreground" />
                        </button>
                        <AppearanceDropdown />

                        <DropdownMenu>
                            <DropdownMenuTrigger class="flex items-center gap-2.5 rounded-full border border-border/60 bg-background py-1 pl-1 pr-3 hover:border-foreground/20 transition">
                                <span class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                    {{ getInitials(page.props.auth.user?.name ?? '?') }}
                                </span>
                                <div class="hidden min-w-0 text-left sm:block">
                                    <div class="truncate text-sm font-medium leading-tight">{{ page.props.auth.user?.name }}</div>
                                    <div class="truncate text-[11px] leading-tight text-muted-foreground">{{ page.props.auth.user?.email }}</div>
                                </div>
                                <ChevronDown class="size-4 text-muted-foreground" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <UserMenuContent :user="page.props.auth.user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                <!-- Page content -->
                <main class="min-w-0 flex-1">
                    <Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" class="mb-5" />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
