<template>
    <Head title="Welcome" />
    <div class="relative flex min-h-screen flex-col bg-background text-foreground overflow-hidden">

        <!-- Background glows -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 left-1/4 h-[28rem] w-[28rem] rounded-full bg-forest/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-moss/8 blur-3xl"></div>
        </div>

        <!-- Header -->
        <header class="relative z-10 flex items-center justify-between px-6 py-5 sm:px-10">
            <div class="flex items-center gap-2.5">
                <span class="flex size-9 items-center justify-center rounded-xl bg-forest text-paper shadow-sm shadow-forest/30">
                    <span class="font-serif text-xl leading-none">b</span>
                </span>
                <span class="font-sans text-base font-semibold tracking-tight">bob</span>
            </div>
            <div class="flex items-center gap-2">
                <template v-if="!page.props.auth?.user">
                    <Link
                        href="/login"
                        class="rounded-full px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >Log in</Link>
                    <Link
                        href="/register"
                        class="rounded-full bg-moss px-4 py-2 text-sm font-medium text-paper shadow-sm shadow-moss/20 hover:bg-moss/90 transition-all"
                    >Get started</Link>
                </template>
                <template v-else>
                    <Link
                        href="/dashboard"
                        class="rounded-full bg-moss px-4 py-2 text-sm font-medium text-paper shadow-sm shadow-moss/20 hover:bg-moss/90 transition-all"
                    >Go to dashboard →</Link>
                </template>
            </div>
        </header>

        <!-- Hero -->
        <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-16 text-center sm:px-10">
            <div class="animate-reveal">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-moss/25 bg-moss/8 px-3 py-1 text-xs font-medium text-moss mb-6">
                    <span class="size-1.5 rounded-full bg-moss"></span>
                    Open · Private · Yours
                </span>
            </div>

            <h1 class="animate-reveal stagger-1 max-w-2xl font-sans text-5xl font-semibold tracking-tight sm:text-6xl leading-tight">
                Your space to<br />
                <span class="text-forest dark:text-moss">write, share,</span> and connect
            </h1>

            <p class="animate-reveal stagger-2 mt-6 max-w-lg text-base text-muted-foreground leading-relaxed">
                bob is a simple, thoughtful social platform — post what matters, follow conversations, and keep it real.
            </p>

            <div class="animate-reveal stagger-3 mt-10 flex flex-wrap items-center justify-center gap-3">
                <template v-if="!page.props.auth?.user">
                    <Link
                        href="/register"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-forest px-6 text-sm font-semibold text-paper shadow-md shadow-forest/25 hover:bg-forest/90 hover:-translate-y-0.5 transition-all duration-200"
                    >
                        Create your account
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </Link>
                    <Link
                        href="/login"
                        class="inline-flex h-11 items-center gap-2 rounded-full border border-border/70 bg-card/60 px-6 text-sm font-medium backdrop-blur hover:border-moss/40 hover:bg-card transition-all duration-200"
                    >
                        Sign in
                    </Link>
                </template>
                <template v-else>
                    <Link
                        href="/feed"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-forest px-6 text-sm font-semibold text-paper shadow-md shadow-forest/25 hover:bg-forest/90 hover:-translate-y-0.5 transition-all duration-200"
                    >
                        Go to your feed →
                    </Link>
                </template>
            </div>

            <!-- Feature pills -->
            <div class="animate-reveal stagger-4 mt-16 flex flex-wrap justify-center gap-2.5">
                <span
                    v-for="pill in pills"
                    :key="pill"
                    class="rounded-full border border-border/50 bg-card/60 px-4 py-1.5 text-xs text-muted-foreground backdrop-blur"
                >{{ pill }}</span>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 py-6 text-center text-xs text-muted-foreground">
            Built with Laravel &amp; Vue &middot; <Link href="/privacy" class="hover:text-moss transition-colors">Privacy</Link>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const pills = [
    'Write posts', 'Reply to comments', 'Like &amp; engage',
    'Report abuse', 'Manage your profile', 'Dark mode',
];
</script>
