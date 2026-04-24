<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import Breadcrumbs from '@/components/breadcrumbs.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserInfo from '@/components/user-info.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Compass, MessageSquare, Search, Users } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({ breadcrumbs: { type: Array, default: () => [] } });

const page = usePage();

const today = new Date();
const dateLine = today.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
const issueNo = String(
    Math.floor((today - new Date(today.getFullYear(), 0, 0)) / 86400000)
).padStart(3, '0');

const nav = [
    { href: '/dashboard', label: 'Front Page', icon: Compass },
    { href: '/friends', label: 'People', icon: Users },
    { href: '/messages', label: 'Messages', icon: MessageSquare },
    { href: '/notifications', label: 'Notices', icon: Bell },
];

const isActive = (href) => page.url === href || (href !== '/' && page.url.startsWith(href));
</script>

<template>
    <div class="min-h-screen bg-paper text-ink">
        <!-- Editorial masthead -->
        <header class="grain relative border-b border-hairline bg-paper">
            <!-- Top meta rule -->
            <div class="rule-b">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground sm:px-8">
                    <span class="inline-flex items-center gap-2">
                        <span class="text-rust">&sect;</span>
                        {{ dateLine }}
                    </span>
                    <span class="hidden sm:inline">Vol. I &middot; No. {{ issueNo }}</span>
                    <span class="inline-flex items-center gap-1">
                        <span>all systems</span>
                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-moss"></span>
                    </span>
                </div>
            </div>

            <!-- Masthead wordmark + user -->
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-5 sm:px-8">
                <Link href="/dashboard" class="group flex items-end gap-3">
                    <span class="font-serif text-5xl leading-none tracking-tight">bob<span class="text-rust">.</span></span>
                    <span class="hidden pb-2 font-mono text-[10px] uppercase tracking-[0.28em] text-muted-foreground sm:inline">
                        a social journal
                    </span>
                </Link>

                <div class="flex items-center gap-2">
                    <button class="inline-flex h-9 items-center gap-2 rule-l rule-r px-4 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground hover:text-ink">
                        <Search class="size-3.5" />
                        <span class="hidden sm:inline">Search</span>
                    </button>
                    <AppearanceDropdown />
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center gap-2 rule-l pl-3 pr-1 py-1 hover:text-rust transition-colors">
                            <UserInfo :user="page.props.auth.user" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="page.props.auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Section nav -->
            <nav class="rule-t">
                <div class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-4 py-3 sm:px-8">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'group relative inline-flex items-center gap-2 whitespace-nowrap font-mono text-[11px] uppercase tracking-[0.22em] transition-colors',
                            isActive(item.href) ? 'text-ink' : 'text-muted-foreground hover:text-ink',
                        ]"
                    >
                        <component :is="item.icon" class="size-3.5" />
                        <span>{{ item.label }}</span>
                        <span
                            v-if="isActive(item.href)"
                            class="pointer-events-none absolute -bottom-[13px] left-0 right-0 h-[2px] bg-rust"
                        ></span>
                    </Link>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-8">
            <Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" class="mb-6" />
            <slot />
        </main>

        <footer class="mt-20 rule-t">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-6 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground sm:px-8">
                <span>&copy; bob, {{ today.getFullYear() }}</span>
                <span class="text-rust">&bull;</span>
                <span>all dispatches composed by humans</span>
            </div>
        </footer>
    </div>
</template>
