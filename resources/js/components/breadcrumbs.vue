<script setup lang="ts">
import type { BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

defineProps<{ breadcrumbs: BreadcrumbItem[] }>();
</script>

<template>
    <nav v-if="breadcrumbs.length" class="flex items-center text-sm text-muted-foreground">
        <template v-for="(item, i) in breadcrumbs" :key="i">
            <ChevronRight v-if="i > 0" class="mx-1 size-3 opacity-50" />
            <Link v-if="i < breadcrumbs.length - 1" :href="item.href" class="hover:text-foreground">{{ item.title }}</Link>
            <span v-else class="font-medium text-foreground">{{ item.title }}</span>
        </template>
    </nav>
</template>
