<template>
    <div class="flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center overflow-hidden rounded-xl bg-forest text-paper shadow-sm shadow-forest/30 ring-1 ring-black/5 dark:ring-white/10">
            <img v-if="appLogo" :src="appLogo" alt="" class="size-full object-cover" />
            <span v-else class="font-serif text-lg leading-none">{{ appName.slice(0, 1).toLowerCase() }}</span>
        </span>
        <span class="font-sans text-sm font-semibold tracking-tight">{{ appName }}</span>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const branding = computed(() => page.props.branding ?? {});
const appName = computed(() => branding.value.name || 'bob');
const appLogo = computed(() => branding.value.logo || null);
</script>
