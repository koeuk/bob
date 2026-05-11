<template>
    <div data-slot="card-content" :class="cn('px-6', $props.class)"><slot /></div>
</template>

<script setup>
import { cn } from '@/lib/utils';
</script>
