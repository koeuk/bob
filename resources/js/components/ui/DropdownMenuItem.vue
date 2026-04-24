<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuItem, type DropdownMenuItemProps, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps<DropdownMenuItemProps & { class?: string; inset?: boolean }>();
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
