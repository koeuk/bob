<script setup>
import { DropdownMenuRoot } from 'radix-vue';

defineProps({ open: Boolean, defaultOpen: Boolean, modal: { type: Boolean, default: true } });
defineEmits(['update:open']);
</script>

<template>
    <DropdownMenuRoot v-bind="$attrs"><slot /></DropdownMenuRoot>
</template>
