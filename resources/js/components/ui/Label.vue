<script setup>
import { cn } from '@/lib/utils';
import { Label as RLabel } from 'radix-vue';

</script>

<template>
    <RLabel
        data-slot="label"
        :class="cn('text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50', $props.class)"
        v-bind="$attrs"
    >
        <slot />
    </RLabel>
</template>
