<script setup>
import { cn } from '@/lib/utils';
import { AvatarFallback } from 'radix-vue';
</script>

<template>
    <AvatarFallback :class="cn('flex size-full items-center justify-center rounded-full bg-muted', $props.class)"><slot /></AvatarFallback>
</template>
