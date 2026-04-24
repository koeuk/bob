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

const form = useForm({ current_password: '', password: '', password_confirmation: '' });

function submit() {
    form.put('/user/password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Password" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Update password" description="Use a strong password you don't reuse elsewhere" />
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <Input id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" />
                        <InputError :message="form.errors.current_password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm new password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            Save
                        </Button>
                        <span v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</span>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
