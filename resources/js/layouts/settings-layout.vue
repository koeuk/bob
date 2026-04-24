<script setup>
import Heading from '@/components/heading.vue';
import Separator from '@/components/ui/Separator.vue';
import { cn } from '@/lib/utils';
import { edit as appearance } from '@/routes/appearance';
import { edit as password } from '@/routes/password';
import { edit as profile } from '@/routes/profile';
import { show as twoFactor } from '@/routes/two-factor';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const sidebarItems = [
    { href: profile.url(), title: 'Profile' },
    { href: password.url(), title: 'Password' },
    { href: twoFactor.url(), title: 'Two-Factor Auth' },
    { href: appearance.url(), title: 'Appearance' },
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
