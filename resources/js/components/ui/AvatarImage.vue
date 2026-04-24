<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AvatarImage, type AvatarImageProps } from 'radix-vue';
defineProps<AvatarImageProps & { class?: string }>();
</script>

<template>
    <AvatarImage v-bind="$attrs" :class="cn('aspect-square size-full', $props.class)" />
</template>
