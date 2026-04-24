<script setup>
import { cn } from '@/lib/utils';
import { LoaderCircle } from 'lucide-vue-next';

</script>

<template>
    <LoaderCircle data-slot="spinner" :class="cn('size-4 animate-spin', $props.class)" />
</template>
