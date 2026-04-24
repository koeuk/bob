<script setup>
import { cn } from '@/lib/utils';
import { Separator as RSeparator } from 'radix-vue';

const props = defineProps({
    orientation: { type: String, default: 'horizontal' },
    decorative: { type: Boolean, default: true },
    class: String,
});
</script>

<template>
    <RSeparator
        data-slot="separator-root"
        :decorative="props.decorative"
        :orientation="props.orientation"
        :class="cn('bg-border shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px', $props.class)"
    />
</template>
