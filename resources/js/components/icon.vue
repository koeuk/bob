<template>
    <component :is="iconNode" :class="cn($props.class)" />
</template>

<script setup>
import { cn } from '@/lib/utils';

</script>
