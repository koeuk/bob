<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuSeparator as RSep, type DropdownMenuSeparatorProps } from 'radix-vue';
defineProps<DropdownMenuSeparatorProps & { class?: string }>();
</script>

<template>
    <RSep :class="cn('-mx-1 my-1 h-px bg-muted', $props.class)" />
</template>
