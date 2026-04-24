<script setup lang="ts">
import { DropdownMenuGroup, type DropdownMenuGroupProps } from 'radix-vue';
defineProps<DropdownMenuGroupProps>();
</script>

<template>
    <DropdownMenuGroup v-bind="$attrs"><slot /></DropdownMenuGroup>
</template>
