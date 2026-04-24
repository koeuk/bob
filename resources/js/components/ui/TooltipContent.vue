<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TooltipContent, type TooltipContentEmits, type TooltipContentProps, TooltipPortal, useForwardPropsEmits } from 'radix-vue';

const props = withDefaults(defineProps<TooltipContentProps & { class?: string }>(), { sideOffset: 4 });
const emits = defineEmits<TooltipContentEmits>();
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
