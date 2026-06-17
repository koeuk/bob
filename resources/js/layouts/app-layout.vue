<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-border/50 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <!-- Logo -->
                <div>
                    <Link href="/dashboard" class="group flex shrink-0 items-center gap-2.5 w-fit">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-forest text-paper shadow-sm shadow-forest/30 transition-transform duration-200 group-hover:scale-105">
                            <span class="font-serif text-xl leading-none">b</span>
                        </span>
                        <span class="font-sans text-base font-semibold tracking-tight">bob</span>
                    </Link>
                </div>

                <!-- Right actions -->
                <div class="flex items-center justify-end gap-2">
                    <AppearanceDropdown />
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="flex items-center gap-2 rounded-full border-2 border-white dark:border-white/10 shadow-sm bg-card/70 py-1 pl-1 pr-3 shadow-sm transition-all duration-150 hover:border-moss/40 hover:shadow-moss/10"
                        >
                            <span class="flex size-7 items-center justify-center rounded-full bg-forest text-paper text-xs font-semibold">
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

        <div class="mx-auto flex gap-5 px-4 pb-10 pt-5 sm:px-6">
            <!-- Sidebar -->
            <aside class="sticky top-20 hidden h-[calc(100vh-6rem)] w-56 shrink-0 flex-col justify-between rounded-2xl border border-border/50 bg-card/80 py-4 shadow-sm backdrop-blur md:flex">
                <nav class="flex flex-col gap-0.5 px-2">
                    <Link
                        v-for="item in railNav"
                        :key="item.label"
                        :href="item.href"
                        :class="[
                            'group inline-flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150',
                            isActive(item.href)
                                ? 'bg-moss text-paper shadow-sm shadow-moss/20'
                                : 'text-muted-foreground hover:bg-moss/8 hover:text-moss',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="['size-4 shrink-0 transition-transform duration-150', isActive(item.href) ? '' : 'group-hover:scale-110']"
                        />
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
                <div class="px-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="inline-flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left transition-all duration-150 hover:bg-secondary"
                        >
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-forest text-paper text-xs font-semibold">
                                {{ initials }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium leading-tight">{{ user?.name }}</span>
                                <span class="block truncate text-[11px] leading-tight text-muted-foreground">{{ user?.email }}</span>
                            </span>
                            <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="top" align="start" class="w-52">
                            <DropdownMenuItem as-child>
                                <Link href="/dashboard" class="block w-full" prefetch>
                                    <LayoutGrid class="mr-2 size-4" /> App
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="isModerator" as-child>
                                <Link href="/admin/dashboard" class="block w-full" prefetch>
                                    <Shield class="mr-2 size-4" /> Admin
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="block w-full text-destructive"
                                >
                                    <LogOut class="mr-2 size-4" /> Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </aside>

            <!-- Main content -->
            <main class="min-w-0 flex-1 space-y-5 animate-reveal">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-to-class="-translate-y-1 opacity-0"
                >
                    <div
                        v-if="page.props.flash?.status"
                        class="flex items-center gap-2.5 rounded-xl border border-moss/20 bg-moss/8 px-4 py-3 text-sm font-medium text-moss"
                    >
                        <span class="size-1.5 rounded-full bg-moss"></span>
                        {{ page.props.flash.status }}
                    </div>
                </Transition>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronsUpDown, Compass, Flag, LayoutGrid, LogOut, Newspaper, Settings as SettingsIcon, Shield } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({ breadcrumbs: { type: Array, default: () => [] } });

const page = usePage();

const user = computed(() => page.props.auth?.user);
const initials = computed(() => {
    const name = user.value?.name ?? '';
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || 'U';
});
const isModerator = computed(() => ['moderator', 'admin', 'super_admin'].includes(user.value?.role));

const railNav = computed(() => [
    { href: '/dashboard',      label: 'Overview',  icon: Compass },
    { href: '/feed',           label: 'Feed',      icon: LayoutGrid },
    { href: '/posts/mine',     label: 'My Posts',  icon: Newspaper },
    { href: '/reports/mine',   label: 'Reports',   icon: Flag },
    { href: '/settings/profile', label: 'Settings', icon: SettingsIcon },
]);

const isActive = (href) =>
    href && (page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?'));
</script>
