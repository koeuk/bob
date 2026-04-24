<script setup>
import { cn } from '@/lib/utils';
import { CheckIcon } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({ class: String, checked: [Boolean, String], defaultChecked: [Boolean, String], disabled: Boolean, name: String, value: String, id: String });
const emits = defineEmits(['update:checked']);

const delegated = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegated, emits);
</script>

<template>
    <CheckboxRoot
        v-bind="forwarded"
        data-slot="checkbox"
        :class="cn('peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50', props.class)"
    >
        <CheckboxIndicator data-slot="checkbox-indicator" class="flex items-center justify-center text-current transition-none">
            <CheckIcon class="size-3.5" />
        </CheckboxIndicator>
    </CheckboxRoot>
</template>
