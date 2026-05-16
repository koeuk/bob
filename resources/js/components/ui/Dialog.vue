<template>
    <DialogRoot :open="open" :default-open="defaultOpen" :modal="modal" @update:open="$emit('update:open', $event)">
        <slot />
    </DialogRoot>
</template>

<script setup>
import { DialogRoot } from 'radix-vue';

defineProps({ open: Boolean, defaultOpen: Boolean, modal: { type: Boolean, default: true } });
defineEmits(['update:open']);
</script>
