<script setup>
import { cn } from '@/lib/utils';

</script>

<template>
    <component :is="iconNode" :class="cn($props.class)" />
</template>
