<script setup>
import { cn } from '@/lib/utils';
</script>

<template>
    <div data-slot="skeleton" :class="cn('bg-accent animate-pulse rounded-md', $props.class)" />
</template>
