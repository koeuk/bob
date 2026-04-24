<script setup>
import { cn } from '@/lib/utils';
import { DropdownMenuSeparator as RSep } from 'radix-vue';
</script>

<template>
    <RSep :class="cn('-mx-1 my-1 h-px bg-muted', $props.class)" />
</template>
