<script setup lang="ts">
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { store } from '@/routes/password';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

function submit() {
    form.post(store.url(), { onFinish: () => form.reset() });
}
</script>

<template>
    <AuthLayout title="Confirm password" description="Confirm your password to continue">
        <Head title="Confirm password" />
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input id="password" v-model="form.password" type="password" required autofocus autocomplete="current-password" />
                <InputError :message="form.errors.password" />
            </div>
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Confirm
            </Button>
        </form>
    </AuthLayout>
</template>
