<template>
    <AuthLayout title="Two-factor authentication" description="Enter the 6-digit code from your authenticator app">
        <Head title="2FA challenge" />
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <template v-if="!useRecovery">
                <div class="grid gap-2">
                    <Label for="code">Authentication code</Label>
                    <Input id="code" v-model="form.code" required autofocus inputmode="numeric" maxlength="6" autocomplete="one-time-code" />
                    <InputError :message="form.errors.code" />
                </div>
            </template>

<script setup>
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const useRecovery = ref(false);

const form = useForm({ code: '', recovery_code: '' });

function submit() {
    form.post('/two-factor-challenge');
}
</script>
