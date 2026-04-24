<script setup>
import AppearanceDropdown from '@/components/appearance-dropdown.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ title: String, description: String });

const year = new Date().getFullYear();
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-paper px-4 py-10 text-ink">
        <!-- corner: theme toggle -->
        <div class="absolute right-4 top-4">
            <AppearanceDropdown />
        </div>

        <!-- brand wordmark -->
        <Link href="/" class="mb-8 inline-flex items-baseline gap-1 select-none">
            <span class="font-serif text-4xl leading-none tracking-tight">bob</span>
            <span class="font-serif text-4xl leading-none text-rust">.</span>
        </Link>

        <!-- card -->
        <div
            class="w-full max-w-sm rounded-md border border-hairline bg-card text-card-foreground shadow-[0_1px_0_rgba(0,0,0,0.02),0_6px_24px_-6px_rgba(0,0,0,0.08)]"
        >
            <div class="px-8 pt-8">
                <!-- section label -->
                <div class="mb-6 flex items-center gap-2 font-mono text-[10px] uppercase tracking-[0.24em] text-muted-foreground">
                    <span class="text-rust">&sect;</span>
                    <span>Access</span>
                    <span class="h-px flex-1 bg-hairline"></span>
                </div>

                <h1 v-if="title" class="font-serif text-3xl leading-tight tracking-tight">
                    {{ title }}<span class="text-rust">.</span>
                </h1>
                <p v-if="description" class="mt-2 font-serif text-base italic leading-snug text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div class="px-8 pb-8 pt-6">
                <slot />
            </div>
        </div>

        <!-- footer -->
        <p class="mt-6 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
            &copy; bob, {{ year }} &middot; a social journal
        </p>
    </div>
</template>
