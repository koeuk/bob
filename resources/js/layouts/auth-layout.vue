<script setup>
import AppLogoIcon from '@/components/app-logo-icon.vue';
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-6">
            <Link :href="home.url()" class="flex items-center gap-2 self-center font-medium">
                <div class="flex size-9 items-center justify-center rounded-md">
                    <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <div v-if="title || description" class="flex flex-col items-center gap-1 text-center">
                    <h1 v-if="title" class="text-xl font-medium">{{ title }}</h1>
                    <p v-if="description" class="text-center text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
