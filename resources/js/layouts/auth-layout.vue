<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background px-4 py-10 text-foreground overflow-hidden">
        <!-- Ambient glows -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-48 -top-48 h-96 w-96 rounded-full bg-forest/10 blur-3xl animate-reveal"></div>
            <div class="absolute -bottom-48 -right-48 h-[28rem] w-[28rem] rounded-full bg-moss/12 blur-3xl animate-reveal stagger-2"></div>
            <div class="absolute left-1/2 top-1/4 h-64 w-64 -translate-x-1/2 rounded-full bg-moss/6 blur-2xl"></div>
        </div>

        <!-- Theme toggle -->
        <div class="absolute right-4 top-4 z-10">
            <AppearanceDropdown />
        </div>

        <div class="relative z-10 flex w-full max-w-sm flex-col items-center">
            <!-- Logo -->
            <Link href="/" class="mb-7 inline-flex items-center gap-2.5 select-none group">
                <span class="flex size-10 items-center justify-center rounded-xl bg-forest text-paper shadow-md shadow-forest/30 transition-transform duration-200 group-hover:scale-105">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 20V4h8a4 4 0 0 1 1.7 7.6A4.5 4.5 0 0 1 15 20H7Z" />
                    </svg>
                </span>
                <span class="text-2xl font-semibold tracking-tight">bob</span>
            </Link>

            <!-- Card -->
            <div class="w-full animate-scale-in rounded-2xl border border-border/70 bg-card/85 shadow-lg shadow-forest/5 backdrop-blur-sm ring-1 ring-moss/8">
                <div class="px-7 pt-7">
                    <h1 v-if="title" class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="mt-1.5 text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <div class="px-7 pb-7 pt-6">
                    <slot />
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-6 text-xs text-muted-foreground">
                Protected by bob &middot;
                <Link href="/privacy" class="hover:text-moss transition-colors duration-150">Privacy</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ title: String, description: String });
</script>
