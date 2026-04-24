<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ title: String, description: String });
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background px-4 py-10 text-foreground">
        <!-- ambient glow -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-40 -top-40 h-96 w-96 rounded-full bg-primary/15 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-accent/15 blur-3xl"></div>
        </div>

        <!-- theme toggle -->
        <div class="absolute right-4 top-4 z-10">
            <AppearanceDropdown />
        </div>

        <div class="relative z-10 flex w-full max-w-sm flex-col items-center">
            <!-- wordmark -->
            <Link href="/" class="mb-6 inline-flex items-center gap-2 select-none">
                <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/25">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 20V4h8a4 4 0 0 1 1.7 7.6A4.5 4.5 0 0 1 15 20H7Z" />
                    </svg>
                </span>
                <span class="text-2xl font-semibold tracking-tight">bob</span>
            </Link>

            <!-- card -->
            <div class="w-full rounded-2xl border border-border/80 bg-card/80 backdrop-blur-sm shadow-[0_1px_0_rgba(255,255,255,0.5)_inset,0_8px_32px_-12px_rgba(0,0,0,0.12)]">
                <div class="px-7 pt-7">
                    <h1 v-if="title" class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="mt-2 text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <div class="px-7 pb-7 pt-6">
                    <slot />
                </div>
            </div>

            <!-- footer -->
            <p class="mt-6 text-xs text-muted-foreground">
                Protected by bob &middot; <Link href="/privacy" class="hover:text-foreground">Privacy</Link>
            </p>
        </div>
    </div>
</template>
