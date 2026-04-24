<script setup>
import { cn } from '@/lib/utils';
import { cva } from 'class-variance-authority';
import { computed } from 'vue';

const alertVariants = cva(
    'relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current',
    {
        variants: {
            variant: {
                default: 'bg-background text-foreground',
                destructive: 'text-destructive-foreground [&>svg]:text-current *:data-[slot=alert-description]:text-destructive-foreground/80',
            },
        },
        defaultVariants: { variant: 'default' },
    },
);

const props = defineProps({
    variant: String,
    class: String,
});

const classes = computed(() => cn(alertVariants({ variant: props.variant }), props.class));
</script>

<template>
    <div role="alert" data-slot="alert" :class="classes"><slot /></div>
</template>
