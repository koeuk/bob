<script setup>
import Heading from '@/components/heading.vue';
import Separator from '@/components/ui/Separator.vue';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const sidebarItems = [
    { href: '/settings/profile', title: 'Profile' },
    { href: '/settings/password', title: 'Password' },
    { href: '/settings/two-factor', title: 'Two-Factor Auth' },
    { href: '/settings/appearance', title: 'Appearance' },
];
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:flex-row md:space-x-12 md:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1">
                    <Link
                        v-for="item in sidebarItems"
                        :key="item.href"
                        :href="item.href"
                        :class="cn('rounded-md px-3 py-1.5 text-sm', page.url === item.href ? 'bg-accent font-medium' : 'hover:bg-accent')"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
