<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuLabel as RLabel, type DropdownMenuLabelProps } from 'radix-vue';
defineProps<DropdownMenuLabelProps & { class?: string; inset?: boolean }>();
</script>

<template>
    <RLabel :class="cn('px-2 py-1.5 text-sm font-semibold', $props.inset && 'pl-8', $props.class)"><slot /></RLabel>
</template>
