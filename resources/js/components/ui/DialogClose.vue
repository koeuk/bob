<template>
    <RDialogClose v-bind="$attrs"><slot /></RDialogClose>
</template>

<script setup>
import { DialogClose as RDialogClose } from 'radix-vue';
</script>
