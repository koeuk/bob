<script setup>
import { DialogTrigger as RDialogTrigger } from 'radix-vue';

</script>

<template>
    <RDialogTrigger v-bind="$attrs"><slot /></RDialogTrigger>
</template>
