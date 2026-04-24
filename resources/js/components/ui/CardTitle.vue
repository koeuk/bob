<script setup>
import { cn } from '@/lib/utils';
</script>

<template>
    <div data-slot="card-title" :class="cn('leading-none font-semibold', $props.class)"><slot /></div>
</template>
