<script setup>
import { TooltipProvider, TooltipRoot, useForwardPropsEmits } from 'radix-vue';

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TooltipProvider>
        <TooltipRoot v-bind="forwarded"><slot /></TooltipRoot>
    </TooltipProvider>
</template>
