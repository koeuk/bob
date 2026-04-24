<script setup>
import { DropdownMenuRoot, useForwardPropsEmits } from 'radix-vue';

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuRoot v-bind="forwarded"><slot /></DropdownMenuRoot>
</template>
