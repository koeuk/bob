<script setup>
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ token: String, email: String });

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <AuthLayout title="Reset password" description="Enter a new password for your account">
        <Head title="Reset password" />
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" required autocomplete="email" readonly />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input id="password" v-model="form.password" type="password" required autofocus autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Reset password
            </Button>
        </form>
    </AuthLayout>
</template>
