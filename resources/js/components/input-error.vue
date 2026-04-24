<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{ message?: string; class?: string }>();
</script>

<template>
    <p v-if="message" :class="cn('text-sm text-destructive', $props.class)">{{ message }}</p>
</template>
