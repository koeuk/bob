<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Component } from 'vue';

defineProps<{ iconNode: Component; class?: string }>();
</script>

<template>
    <component :is="iconNode" :class="cn($props.class)" />
</template>
