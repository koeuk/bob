<script setup>
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import {  useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();

const currentIcon = computed(() => {
    if (appearance.value === 'dark') return Moon;
    if (appearance.value === 'light') return Sun;
    return Monitor;
});

const options = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'system', label: 'System' },
];
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger class="inline-flex size-9 items-center justify-center rounded-md hover:bg-accent">
            <component :is="currentIcon" class="size-4" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem v-for="o in options" :key="o.value" @click="updateAppearance(o.value)">
                {{ o.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
