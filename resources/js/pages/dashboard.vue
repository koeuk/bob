<script setup>
import AppLayout from '@/layouts/app-layout.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border bg-card p-6"><div class="text-sm text-muted-foreground">Placeholder card 1</div></div>
            <div class="rounded-xl border bg-card p-6"><div class="text-sm text-muted-foreground">Placeholder card 2</div></div>
            <div class="rounded-xl border bg-card p-6"><div class="text-sm text-muted-foreground">Placeholder card 3</div></div>
        </div>
    </AppLayout>
</template>
