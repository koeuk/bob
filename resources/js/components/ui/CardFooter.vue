<template>
    <div data-slot="card-footer" :class="cn('flex items-center px-6', $props.class)"><slot /></div>
</template>

<script setup>
import { cn } from '@/lib/utils';
</script>
