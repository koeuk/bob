<script setup>
import { cn } from '@/lib/utils';
import { AvatarRoot } from 'radix-vue';
</script>

<template>
    <AvatarRoot :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', $props.class)"><slot /></AvatarRoot>
</template>
