<template>
    <div class="px-4 py-6 animate-reveal">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Settings</h1>
            <p class="mt-1 text-sm text-muted-foreground">Manage your profile and account settings</p>
        </div>

        <div class="flex flex-col gap-8 md:flex-row md:gap-12">
            <!-- Sidebar nav -->
            <aside class="w-full md:w-44 shrink-0">
                <nav class="flex flex-col gap-0.5">
                    <Link
                        v-for="item in sidebarItems"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150',
                            page.url === item.href
                                ? 'bg-moss/12 text-moss font-semibold'
                                : 'text-muted-foreground hover:bg-secondary hover:text-ink',
                        ]"
                    >
                        <span
                            v-if="page.url === item.href"
                            class="size-1.5 rounded-full bg-moss shrink-0"
                        ></span>
                        <span v-else class="size-1.5 rounded-full bg-transparent shrink-0"></span>
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="md:hidden" />

            <!-- Content -->
            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import Separator from '@/components/ui/Separator.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const sidebarItems = [
    { href: '/settings/profile',    title: 'Profile' },
    { href: '/settings/password',   title: 'Password' },
    { href: '/settings/two-factor', title: 'Two-Factor Auth' },
    { href: '/settings/appearance', title: 'Appearance' },
];
</script>
