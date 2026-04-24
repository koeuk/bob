<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AvatarRoot, type AvatarRootProps } from 'radix-vue';
defineProps<AvatarRootProps & { class?: string }>();
</script>

<template>
    <AvatarRoot :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', $props.class)"><slot /></AvatarRoot>
</template>
