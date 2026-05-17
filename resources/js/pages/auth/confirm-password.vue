<template>
    <Head title="Confirm password" />
    <AppLayout>
        <div class="flex min-h-[60vh] items-center justify-center">
            <div class="w-full max-w-md space-y-6 rounded-3xl border border-border/60 bg-card p-8 shadow-sm animate-scale-in">
                <div>
                    <h2 class="font-sans text-2xl font-semibold tracking-tight">Confirm password</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Please re-enter your password to continue.</p>
                </div>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <Label for="password">Password</Label>
                        <Input id="password" v-model="form.password" type="password" required autofocus autocomplete="current-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Confirm
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AppLayout from '@/layouts/app-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

function submit() {
    form.post('/user/confirm-password', { onFinish: () => form.reset() });
}
</script>
