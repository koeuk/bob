<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" show-email />
        </div>
    </DropdownMenuLabel>
    <template v-if="!isAdmin">
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
            <DropdownMenuItem as-child>
                <Link href="/dashboard" class="block w-full" prefetch>
                    <Compass class="mr-2 size-4" /> Overview
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link href="/feed" class="block w-full" prefetch>
                    <LayoutGrid class="mr-2 size-4" /> Feed
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link href="/posts/mine" class="block w-full" prefetch>
                    <Newspaper class="mr-2 size-4" /> My Posts
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link href="/reports/mine" class="block w-full" prefetch>
                    <Flag class="mr-2 size-4" /> Reports
                </Link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
    </template>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem as-child>
            <Link :href="isAdmin ? '/admin/settings' : '/settings/profile'" class="block w-full" prefetch>
                <Settings class="mr-2 size-4" /> Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem as-child>
        <button class="block w-full" @click="handleLogout">
            <LogOut class="mr-2 size-4" /> Log out
        </button>
    </DropdownMenuItem>
</template>

<script setup>
import DropdownMenuGroup from '@/components/ui/DropdownMenuGroup.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuLabel from '@/components/ui/DropdownMenuLabel.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import UserInfo from '@/components/user-info.vue';
import { Link, router } from '@inertiajs/vue3';
import { Compass, Flag, LayoutGrid, LogOut, Newspaper, Settings } from 'lucide-vue-next';

defineProps({
    user: { type: Object, required: true },
    isAdmin: { type: Boolean, default: false },
});

function handleLogout(e) {
    e.preventDefault();
    router.post('/logout');
}
</script>
