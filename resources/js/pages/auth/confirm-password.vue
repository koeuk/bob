<template>
    <Head title="Confirm password" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Confirm password" description="Please re-enter your password to continue." />
                <form class="space-y-4" @submit.prevent="submit">
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
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<script setup>
import HeadingSmall from '@/components/heading-small.vue';
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AppLayout from '@/layouts/app-layout.vue';
import SettingsLayout from '@/layouts/settings-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

function submit() {
    form.post('/user/confirm-password', { onFinish: () => form.reset() });
}
</script>
