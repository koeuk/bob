<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DialogTitle as RDialogTitle, type DialogTitleProps } from 'radix-vue';
defineProps<DialogTitleProps & { class?: string }>();
</script>

<template>
    <RDialogTitle :class="cn('text-lg font-semibold leading-none tracking-tight', $props.class)"><slot /></RDialogTitle>
</template>
