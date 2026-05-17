<template>
    <textarea
        v-bind="$attrs"
        :class="cn('border-input placeholder:text-muted-foreground flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive', props.class)"
        :value="modelValue"
        @input="emit('update:modelValue', $event.target.value)"
    />
</template>

<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: String, default: '' },
    class: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
</script>
