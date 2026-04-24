<script setup>
import { DialogRoot } from 'radix-vue';

defineProps({ open: Boolean, defaultOpen: Boolean, modal: { type: Boolean, default: true } });
defineEmits(['update:open']);
</script>

<template>
    <DialogRoot v-bind="$attrs"><slot /></DialogRoot>
</template>
