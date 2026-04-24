<script setup>
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

</script>

<template>
    <Link :href="href" :as="as ?? 'a'" :method="method ?? 'get'" :class="cn('text-foreground underline underline-offset-4 hover:no-underline', $props.class)">
        <slot />
    </Link>
</template>
