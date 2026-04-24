<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Separator as RSeparator, type SeparatorProps } from 'radix-vue';

const props = withDefaults(defineProps<SeparatorProps & { class?: string }>(), {
    orientation: 'horizontal',
    decorative: true,
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
