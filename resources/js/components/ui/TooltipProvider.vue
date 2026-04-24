<script setup lang="ts">
import { TooltipProvider, type TooltipProviderProps } from 'radix-vue';
defineProps<TooltipProviderProps>();
</script>

<template>
    <TooltipProvider v-bind="$attrs"><slot /></TooltipProvider>
</template>
