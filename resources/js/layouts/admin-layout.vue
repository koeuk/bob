<template>
    <SidebarProvider>
        <Sidebar collapsible="icon">
            <!-- Logo -->
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child class="data-[slot=sidebar-menu-button]:!p-1.5">
                            <Link href="/admin/dashboard">
                                <span class="flex size-8 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-sm shrink-0">
                                    <span class="font-serif text-lg leading-none">b</span>
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="font-semibold tracking-tight text-sidebar-foreground">
                                        bob<span class="opacity-50">/</span>admin
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <!-- Nav items -->
            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem v-for="item in sideNav" :key="item.label">
                                <SidebarMenuButton :is-active="isActive(item.href)" :tooltip="item.label" as-child>
                                    <Link :href="item.href">
                                        <component :is="item.icon" />
                                        <span>{{ item.label }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <!-- Footer -->
            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <SidebarMenuButton size="lg" tooltip="Account" class="data-[state=open]:bg-sidebar-accent">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sidebar-primary text-sidebar-primary-foreground text-xs font-semibold">
                                        {{ initials }}
                                    </span>
                                    <div class="grid flex-1 text-left text-sm leading-tight">
                                        <span class="truncate font-medium">{{ user?.name }}</span>
                                        <span class="truncate text-[11px] text-sidebar-foreground/60">{{ user?.email }}</span>
                                    </div>
                                    <ChevronsUpDown class="ml-auto size-4" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent side="top" align="start" class="w-56">
                                <DropdownMenuItem as-child>
                                    <Link href="/dashboard" class="block w-full" prefetch>
                                        <LayoutGrid class="mr-2 size-4" /> App
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
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
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>

        <SidebarInset>
            <!-- Top bar -->
            <header class="flex h-14 shrink-0 items-center gap-2 border-b border-border/50 bg-background/60 px-4 backdrop-blur">
                <SidebarTrigger class="-ml-1 text-muted-foreground hover:text-moss transition-colors" />
                <Separator orientation="vertical" class="mx-2 h-4" />

                <div class="flex flex-1 items-center justify-end">
                    <!-- Right: appearance + user -->
                    <div class="flex items-center justify-end gap-2">
                        <AppearanceDropdown />
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                class="flex items-center gap-2 rounded-full border-2 border-white dark:border-white/10 shadow-sm bg-card/70 py-1 pl-1 pr-3 shadow-sm transition-all duration-150 hover:border-moss/40"
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
                                <UserMenuContent :user="user" :is-admin="true" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <div class="flex flex-1 flex-col gap-6 p-6 stagger-children">
                <div v-if="title">
                    <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">{{ title }}</h1>
                </div>

                <!-- Flash messages -->
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
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="-translate-y-2 opacity-0"
                >
                    <div
                        v-if="page.props.flash?.error"
                        class="flex items-center gap-2.5 rounded-xl border border-destructive/20 bg-destructive/8 px-4 py-3 text-sm font-medium text-destructive"
                    >
                        <span class="size-1.5 rounded-full bg-destructive"></span>
                        {{ page.props.flash.error }}
                    </div>
                </Transition>

                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>

<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupContent,
    SidebarHeader, SidebarInset, SidebarMenu, SidebarMenuButton, SidebarMenuItem,
    SidebarProvider, SidebarRail, SidebarTrigger,
} from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ActivitySquare, ChevronDown, ChevronsUpDown, FileText, Flag, Gauge, Heart,
    LayoutGrid, LogOut, MessageSquare, Newspaper, Settings as SettingsIcon,
    Shield, ShieldBan, Users,
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

const sideNav = computed(() => [
    { href: '/admin/dashboard',     label: 'Dashboard', icon: Gauge },
    { href: '/admin/users',         label: 'Users',     icon: Users },
    { href: '/admin/reports',       label: 'Reports',   icon: Flag },
    { href: '/admin/bans',          label: 'Bans',      icon: ShieldBan },
    { href: '/admin/posts',         label: 'Posts',     icon: Newspaper },
    { href: '/admin/comments',      label: 'Comments',  icon: MessageSquare },
    { href: '/admin/likes',         label: 'Likes',     icon: Heart },
    { href: '/admin/pages',         label: 'Pages',     icon: FileText },
    ...(isSuperOrAdmin.value ? [{ href: '/admin/settings', label: 'Settings', icon: SettingsIcon }] : []),
    { href: '/admin/activity-logs', label: 'Activity',  icon: ActivitySquare },
]);

const isActive = (href) =>
    page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
</script>
