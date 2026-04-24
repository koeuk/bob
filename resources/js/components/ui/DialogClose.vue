<script setup lang="ts">
import { DialogClose as RDialogClose, type DialogCloseProps } from 'radix-vue';
defineProps<DialogCloseProps>();
</script>

<template>
    <RDialogClose v-bind="$attrs"><slot /></RDialogClose>
</template>
