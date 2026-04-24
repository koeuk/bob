<script setup>
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AtSign, CheckCircle2, Eye, EyeOff, Loader2, Lock } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({ status: String, canResetPassword: Boolean, canRegister: Boolean });

const form = useForm({ email: '', password: '', remember: false });
const showPassword = ref(false);

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout title="Welcome back" description="Enter your email and password to continue">
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-5 flex items-start gap-2 rounded-lg border border-primary/20 bg-primary/5 px-3.5 py-2.5 text-sm text-primary"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" />
            <span>{{ status }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <!-- Email -->
            <div class="space-y-1.5">
                <label for="login-email" class="block text-sm font-medium">Email</label>
                <div class="relative">
                    <AtSign class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        id="login-email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-3 text-sm text-foreground transition placeholder:text-muted-foreground/60 focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30"
                    />
                </div>
                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="login-password" class="block text-sm font-medium">Password</label>
                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        id="login-password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-10 text-sm text-foreground transition placeholder:text-muted-foreground/60 focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30"
                    />
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground hover:text-foreground transition"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" />
                        <Eye v-else class="size-4" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
            </div>

            <!-- Remember -->
            <label class="flex cursor-pointer items-center gap-2.5 select-none pt-1">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="size-4 cursor-pointer rounded border-input accent-primary"
                />
                <span class="text-sm text-muted-foreground">Remember me for 30 days</span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm shadow-primary/20 transition hover:bg-primary/90 disabled:opacity-60"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span>
            </button>

            <!-- Divider + sign up -->
            <div v-if="canRegister" class="pt-3 text-center text-sm text-muted-foreground">
                Don&rsquo;t have an account?
                <Link href="/register" class="font-medium text-foreground hover:text-primary">Sign up</Link>
            </div>
        </form>
    </AuthLayout>
</template>
