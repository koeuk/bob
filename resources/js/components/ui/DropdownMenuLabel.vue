<script setup>
import { cn } from '@/lib/utils';
import { DropdownMenuLabel as RLabel } from 'radix-vue';
</script>

<template>
    <RLabel :class="cn('px-2 py-1.5 text-sm font-semibold', $props.inset && 'pl-8', $props.class)"><slot /></RLabel>
</template>
