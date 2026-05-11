<template>
    <TooltipProvider v-bind="$attrs"><slot /></TooltipProvider>
</template>

<script setup>
import { TooltipProvider } from 'radix-vue';
</script>
