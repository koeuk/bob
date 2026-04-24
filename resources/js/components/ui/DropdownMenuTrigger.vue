<script setup lang="ts">
import { DropdownMenuTrigger, type DropdownMenuTriggerProps } from 'radix-vue';
defineProps<DropdownMenuTriggerProps>();
</script>

<template>
    <DropdownMenuTrigger v-bind="$attrs"><slot /></DropdownMenuTrigger>
</template>
