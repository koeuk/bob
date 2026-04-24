<script setup>
import { cn } from '@/lib/utils';
import { TooltipContent, TooltipPortal, useForwardPropsEmits } from 'radix-vue';

const props = defineProps({
    sideOffset: { type: Number, default: 4 },
    class: String,
});
const emits = defineEmits(['escapeKeyDown', 'pointerDownOutside']);
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            v-bind="forwarded"
            :class="cn('z-50 overflow-hidden rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground animate-in fade-in-0 zoom-in-95', props.class)"
        >
            <slot />
        </TooltipContent>
    </TooltipPortal>
</template>
