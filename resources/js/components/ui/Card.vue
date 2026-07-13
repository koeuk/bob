<template>
    <div
        data-slot="card"
        :class="cn('bg-card text-card-foreground flex flex-col gap-6 rounded-xl border-2 border-white dark:border-white/10 shadow-sm py-6 transition-shadow duration-200 hover:shadow-md', $props.class)"
    >
        <slot />
    </div>
</template>

<script setup>
import { cn } from '@/lib/utils';

</script>
