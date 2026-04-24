<script setup>
import { DialogClose as RDialogClose } from 'radix-vue';
</script>

<template>
    <RDialogClose v-bind="$attrs"><slot /></RDialogClose>
</template>
