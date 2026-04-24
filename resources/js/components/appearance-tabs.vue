<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type Appearance, useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const options: { value: Appearance; icon: typeof Sun; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
];
</script>

<template>
    <div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
        <button
            v-for="o in options"
            :key="o.value"
            type="button"
            :class="cn('flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-sm transition-colors',
                appearance === o.value
                    ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-white'
                    : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white',
            )"
            @click="updateAppearance(o.value)"
        >
            <component :is="o.icon" class="size-4" />
            <span>{{ o.label }}</span>
        </button>
    </div>
</template>
