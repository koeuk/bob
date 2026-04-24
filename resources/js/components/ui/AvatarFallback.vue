<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AvatarFallback, type AvatarFallbackProps } from 'radix-vue';
defineProps<AvatarFallbackProps & { class?: string }>();
</script>

<template>
    <AvatarFallback :class="cn('flex size-full items-center justify-center rounded-full bg-muted', $props.class)"><slot /></AvatarFallback>
</template>
