<script setup lang="ts">
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { store } from '@/routes/two-factor/login';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const useRecovery = ref(false);

const form = useForm({ code: '', recovery_code: '' });

function submit() {
    form.post(store.url());
}
</script>

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
            <template v-else>
                <div class="grid gap-2">
                    <Label for="recovery_code">Recovery code</Label>
                    <Input id="recovery_code" v-model="form.recovery_code" required autofocus autocomplete="one-time-code" />
                    <InputError :message="form.errors.recovery_code" />
                </div>
            </template>

            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Verify
            </Button>

            <button type="button" class="text-center text-sm text-muted-foreground hover:text-foreground" @click="useRecovery = !useRecovery">
                {{ useRecovery ? 'Use authentication code' : 'Use recovery code' }}
            </button>
        </form>
    </AuthLayout>
</template>
