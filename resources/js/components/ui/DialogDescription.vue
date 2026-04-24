<script setup>
import { cn } from '@/lib/utils';
import { DialogDescription as RDialogDescription } from 'radix-vue';
</script>

<template>
    <RDialogDescription :class="cn('text-sm text-muted-foreground', $props.class)"><slot /></RDialogDescription>
</template>
