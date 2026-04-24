<script setup lang="ts">
import { cn } from '@/lib/utils';
import {
    DropdownMenuContent,
    type DropdownMenuContentEmits,
    type DropdownMenuContentProps,
    DropdownMenuPortal,
    useForwardPropsEmits,
} from 'radix-vue';

const props = withDefaults(defineProps<DropdownMenuContentProps & { class?: string }>(), { sideOffset: 4 });
const emits = defineEmits<DropdownMenuContentEmits>();
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
