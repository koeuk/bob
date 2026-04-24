<script setup lang="ts">
import InputError from '@/components/input-error.vue';
import TextLink from '@/components/text-link.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ status?: string }>();

const form = useForm({ email: '' });

function submit() {
    form.post(email.url());
}
</script>

<template>
    <AuthLayout title="Forgot password" description="Enter your email to receive a password reset link">
        <Head title="Forgot password" />

        <div v-if="status" class="mb-4 text-center text-sm text-green-600">{{ status }}</div>

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" required autofocus autocomplete="email" />
                <InputError :message="form.errors.email" />
            </div>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Email password reset link
            </Button>
            <div class="text-center text-sm text-muted-foreground">
                Return to <TextLink :href="login.url()">log in</TextLink>
            </div>
        </form>
    </AuthLayout>
</template>
