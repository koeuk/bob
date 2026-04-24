<script setup>
import { DropdownMenuGroup } from 'radix-vue';
</script>

<template>
    <DropdownMenuGroup v-bind="$attrs"><slot /></DropdownMenuGroup>
</template>
