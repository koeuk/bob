<script setup>
import { cn } from '@/lib/utils';
</script>

<template>
    <div :class="cn('flex flex-col gap-2 text-center sm:text-left', $props.class)"><slot /></div>
</template>
