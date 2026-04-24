<script setup>
import { TooltipTrigger } from 'radix-vue';
</script>

<template>
    <TooltipTrigger v-bind="$attrs"><slot /></TooltipTrigger>
</template>
