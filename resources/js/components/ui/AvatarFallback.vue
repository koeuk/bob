<template>
    <AvatarFallback :class="cn('flex size-full items-center justify-center rounded-full bg-muted', $props.class)"><slot /></AvatarFallback>
</template>

<script setup>
import { cn } from '@/lib/utils';
import { AvatarFallback } from 'radix-vue';
</script>
