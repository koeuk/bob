<script setup>
import { cn } from '@/lib/utils';
import { DropdownMenuItem, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({ class: String, inset: Boolean, asChild: Boolean, disabled: Boolean });

const delegated = computed(() => {
    const { class: _, inset: __, ...rest } = props;
    return rest;
});
const forwarded = useForwardProps(delegated);
</script>

<template>
    <DropdownMenuItem
        v-bind="forwarded"
        :class="cn('relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50', props.inset && 'pl-8', props.class)"
    >
        <slot />
    </DropdownMenuItem>
</template>
