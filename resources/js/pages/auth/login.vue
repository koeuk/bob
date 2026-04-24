<script setup>
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LoaderCircle } from 'lucide-vue-next';

defineProps({ status: String, canResetPassword: Boolean, canRegister: Boolean });

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout title="Welcome back" description="Sign in to continue the conversation.">
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-5 rule-t rule-b py-2 font-mono text-[11px] uppercase tracking-wider text-moss"
        >
            <span class="mr-1 text-rust">&sect;</span>{{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <!-- Email -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                    <label for="login-email">Email address</label>
                    <span v-if="form.errors.email" class="text-destructive">&mdash; {{ form.errors.email }}</span>
                </div>
                <input
                    id="login-email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="hello@bob.world"
                    class="w-full border-0 border-b border-hairline bg-transparent py-2 font-serif text-lg text-ink placeholder:text-muted-foreground/50 focus:border-ink focus:outline-none focus:ring-0 transition-colors"
                />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                    <label for="login-password">Password</label>
                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-rust hover:underline hover:underline-offset-4"
                    >
                        Forgot &rarr;
                    </Link>
                </div>
                <input
                    id="login-password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    class="w-full border-0 border-b border-hairline bg-transparent py-2 font-serif text-lg tracking-[0.15em] text-ink placeholder:text-muted-foreground/50 focus:border-ink focus:outline-none focus:ring-0 transition-colors"
                />
                <p v-if="form.errors.password" class="font-mono text-[10px] uppercase tracking-[0.18em] text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Remember -->
            <label class="flex cursor-pointer items-center gap-2.5 select-none pt-1">
                <span class="relative flex h-4 w-4 shrink-0 items-center justify-center border border-ink">
                    <input v-model="form.remember" type="checkbox" class="peer sr-only" />
                    <span class="absolute inset-0.5 bg-ink opacity-0 peer-checked:opacity-100 transition-opacity"></span>
                </span>
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                    Keep me signed in
                </span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="group relative flex w-full items-center justify-between overflow-hidden bg-ink px-5 py-3.5 text-paper transition-colors hover:bg-moss disabled:opacity-60"
            >
                <span class="font-serif text-lg tracking-tight">
                    {{ form.processing ? 'Signing in&hellip;' : 'Sign in' }}
                </span>
                <span class="flex items-center gap-2 font-mono text-[10px] uppercase tracking-[0.22em] opacity-80">
                    <LoaderCircle v-if="form.processing" class="size-3.5 animate-spin" />
                    <ArrowRight v-else class="size-3.5 transition-transform group-hover:translate-x-1" />
                </span>
                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 w-[3px] bg-rust"></span>
            </button>

            <!-- Sign up -->
            <div v-if="canRegister" class="rule-t pt-4">
                <p class="flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.2em] text-muted-foreground">
                    <span>New here?</span>
                    <Link href="/register" class="group inline-flex items-center gap-1.5 text-ink hover:text-rust">
                        <span class="font-serif text-sm normal-case tracking-normal italic">Make an account</span>
                        <ArrowRight class="size-3 transition-transform group-hover:translate-x-0.5" />
                    </Link>
                </p>
            </div>
        </form>
    </AuthLayout>
</template>
