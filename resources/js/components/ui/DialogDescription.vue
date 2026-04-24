<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DialogDescription as RDialogDescription, type DialogDescriptionProps } from 'radix-vue';
defineProps<DialogDescriptionProps & { class?: string }>();
</script>

<template>
    <RDialogDescription :class="cn('text-sm text-muted-foreground', $props.class)"><slot /></RDialogDescription>
</template>
