<script setup>
import { TooltipProvider } from 'radix-vue';
</script>

<template>
    <TooltipProvider v-bind="$attrs"><slot /></TooltipProvider>
</template>
