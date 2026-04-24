<script setup>
import { TooltipProvider, TooltipRoot } from 'radix-vue';

defineProps({ open: Boolean, defaultOpen: Boolean, delayDuration: Number, disableHoverableContent: Boolean });
defineEmits(['update:open']);
</script>

<template>
    <TooltipProvider>
        <TooltipRoot v-bind="$attrs"><slot /></TooltipRoot>
    </TooltipProvider>
</template>
