<script setup lang="ts">
import { cn } from '@/lib/utils';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{ class?: string }>();
</script>

<template>
    <LoaderCircle data-slot="spinner" :class="cn('size-4 animate-spin', $props.class)" />
</template>
