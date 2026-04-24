<script setup>
import AppLogo from '@/components/app-logo.vue';
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import Breadcrumbs from '@/components/breadcrumbs.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import UserInfo from '@/components/user-info.vue';
import UserMenuContent from '@/components/user-menu-content.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({ breadcrumbs: { type: Array, default: () => [] } });

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b bg-background">
            <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4 sm:px-6">
                <Link href="/dashboard" class="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <nav class="ml-6 hidden items-center gap-4 text-sm md:flex">
                    <Link href="/dashboard" class="text-foreground hover:underline">Dashboard</Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <AppearanceDropdown />
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center gap-2 rounded-md p-1 hover:bg-accent">
                            <UserInfo :user="page.props.auth.user" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="page.props.auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            <Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" class="mb-6" />
            <slot />
        </main>
    </div>
</template>
