<script setup>
import InputError from '@/components/input-error.vue';
import TextLink from '@/components/text-link.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String, canResetPassword: Boolean, canRegister: Boolean });

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">{{ status }}</div>

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" v-model="form.email" type="email" required autofocus autocomplete="email" placeholder="email@example.com" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center">
                    <Label for="password">Password</Label>
                    <TextLink v-if="canResetPassword" href="/forgot-password" class="ml-auto text-sm">Forgot password?</TextLink>
                </div>
                <Input id="password" v-model="form.password" type="password" required autocomplete="current-password" placeholder="Password" />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center gap-2">
                <Checkbox id="remember" :checked="form.remember" @update:checked="(v) => (form.remember = v)" />
                <Label for="remember" class="text-sm">Remember me</Label>
            </div>

            <Button type="submit" :disabled="form.processing" class="w-full">
                <Spinner v-if="form.processing" />
                Log in
            </Button>

            <div v-if="canRegister" class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink href="/register">Sign up</TextLink>
            </div>
        </form>
    </AuthLayout>
</template>
