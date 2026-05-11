<template>
    <div data-slot="card-header" :class="cn('flex flex-col gap-1.5 px-6', $props.class)"><slot /></div>
</template>

<script setup>
import { cn } from '@/lib/utils';
</script>
