<script setup>
import DropdownMenuGroup from '@/components/ui/DropdownMenuGroup.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuLabel from '@/components/ui/DropdownMenuLabel.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import UserInfo from '@/components/user-info.vue';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

defineProps({ user: { type: Object, required: true } });

function handleLogout(e) {
    e.preventDefault();
    router.post('/logout');
}
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" show-email />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem as-child>
            <Link href="/settings/profile" class="block w-full" prefetch>
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
