<script setup lang="ts">
import { login, register } from '@/routes';
import { dashboard } from '@/routes';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>();
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <div class="max-w-2xl text-center space-y-6">
            <h1 class="text-4xl font-semibold tracking-tight">Welcome to {{ page.props.name }}</h1>
            <p class="text-muted-foreground">Laravel 12 · Vue 3 · Inertia</p>
            <div class="flex justify-center gap-3">
                <Link v-if="!page.props.auth.user" :href="login.url()" class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">Log in</Link>
                <Link v-if="!page.props.auth.user" :href="register.url()" class="rounded-md border px-4 py-2 text-sm hover:bg-accent">Register</Link>
                <Link v-else :href="dashboard.url()" class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">Dashboard</Link>
            </div>
        </div>
    </div>
</template>
