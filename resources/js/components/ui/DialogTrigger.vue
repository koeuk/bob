<script setup lang="ts">
import { DialogTrigger as RDialogTrigger, type DialogTriggerProps } from 'radix-vue';

defineProps<DialogTriggerProps>();
</script>

<template>
    <RDialogTrigger v-bind="$attrs"><slot /></RDialogTrigger>
</template>
