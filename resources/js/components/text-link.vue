<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

defineProps<{ href: string; class?: string; as?: string; method?: string }>();
</script>

<template>
    <Link :href="href" :as="as ?? 'a'" :method="method ?? 'get'" :class="cn('text-foreground underline underline-offset-4 hover:no-underline', $props.class)">
        <slot />
    </Link>
</template>
