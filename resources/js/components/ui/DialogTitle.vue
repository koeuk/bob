<template>
    <RDialogTitle :class="cn('text-lg font-semibold leading-none tracking-tight', $props.class)"><slot /></RDialogTitle>
</template>

<script setup>
import { cn } from '@/lib/utils';
import { DialogTitle as RDialogTitle } from 'radix-vue';
</script>
