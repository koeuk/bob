<template>
    <SidebarProvider>
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child class="data-[slot=sidebar-menu-button]:!p-1.5">
                            <Link href="/admin/dashboard">
                                <span class="flex size-8 items-center justify-center rounded-xl bg-rust text-paper shadow-sm shrink-0">
                                    <span class="font-serif text-lg leading-none">b</span>
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="font-semibold tracking-tight">bob<span class="text-rust">/</span>admin</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

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

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton tooltip="Back to app" as-child>
                            <Link href="/dashboard">
                                <ArrowLeft />
                                <span>Back to app</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>

        <SidebarInset>
            <header class="flex h-14 shrink-0 items-center gap-2 border-b border-border/60 px-4">
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mx-2 h-4" />

                <div class="flex flex-1 items-center justify-between">
                    <div class="flex items-center rounded-lg border border-border/60 bg-card/70 p-0.5 shadow-sm">
                        <Link href="/dashboard" class="rounded-md px-3 py-1 text-xs font-medium text-muted-foreground hover:text-ink transition-colors">App</Link>
                        <Link href="/admin/dashboard" class="rounded-md px-3 py-1 text-xs font-medium bg-ink text-paper shadow-sm transition-colors">Admin</Link>
                    </div>

                    <div class="flex items-center gap-2">
                        <AppearanceDropdown />
                        <DropdownMenu>
                            <DropdownMenuTrigger class="flex items-center gap-2 rounded-full border border-border/60 bg-card/70 py-1 pl-1 pr-3 shadow-sm hover:text-rust transition-colors">
                                <span class="flex size-7 items-center justify-center rounded-full bg-ink text-paper text-xs font-semibold">
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

            <div class="flex flex-1 flex-col gap-6 p-6">
                <div v-if="title">
                    <h1 class="font-sans text-3xl font-semibold tracking-tight sm:text-4xl">{{ title }}</h1>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="-translate-y-1 opacity-0"
                >
                    <div v-if="page.props.flash?.status" class="rounded-xl bg-moss/10 px-4 py-3 text-sm text-moss">
                        {{ page.props.flash.status }}
                    </div>
                </Transition>
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                >
                    <div v-if="page.props.flash?.error" class="rounded-xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
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
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ActivitySquare,
    ArrowLeft,
    ChevronDown,
    FileText,
    Flag,
    Gauge,
    Heart,
    MessageSquare,
    Newspaper,
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

const sideNav = computed(() => [
    { href: '/admin/dashboard', label: 'Dashboard', icon: Gauge },
    { href: '/admin/users', label: 'Users', icon: Users },
    { href: '/admin/reports', label: 'Reports', icon: Flag },
    { href: '/admin/bans', label: 'Bans', icon: ShieldBan },
    { href: '/admin/posts', label: 'Posts', icon: Newspaper },
    { href: '/admin/comments', label: 'Comments', icon: MessageSquare },
    { href: '/admin/likes', label: 'Likes', icon: Heart },
    { href: '/admin/pages', label: 'Pages', icon: FileText },
    ...(isSuperOrAdmin.value ? [{ href: '/admin/settings', label: 'Settings', icon: SettingsIcon }] : []),
    { href: '/admin/activity-logs', label: 'Activity', icon: ActivitySquare },
]);

const isActive = (href) => page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
</script>
