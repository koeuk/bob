<script setup lang="ts">
import Avatar from '@/components/ui/Avatar.vue';
import AvatarFallback from '@/components/ui/AvatarFallback.vue';
import AvatarImage from '@/components/ui/AvatarImage.vue';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

defineProps<{ user: User; showEmail?: boolean }>();
const getInitials = useInitials();
</script>

<template>
    <Avatar class="size-8 overflow-hidden rounded-full">
        <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-full bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>
    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
</template>
