<template>
    <AvatarImage v-bind="$attrs" :class="cn('aspect-square size-full', $props.class)" />
</template>

<script setup>
import { cn } from '@/lib/utils';
import { AvatarImage } from 'radix-vue';
</script>
