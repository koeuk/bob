<script setup>
import { cn } from '@/lib/utils';

</script>

<template>
    <p v-if="message" :class="cn('text-sm text-destructive', $props.class)">{{ message }}</p>
</template>
