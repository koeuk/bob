<script setup>
import { cn } from '@/lib/utils';
import {
    DropdownMenuContent,
     
     
    DropdownMenuPortal,
    useForwardPropsEmits,
} from 'radix-vue';

const props = defineProps({
    align: String,
    sideOffset: { type: Number, default: 4 },
    class: String,
});
const emits = defineEmits(['closeAutoFocus', 'escapeKeyDown', 'pointerDownOutside', 'focusOutside', 'interactOutside']);
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            v-bind="forwarded"
            :class="cn('z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md', props.class)"
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
