<script setup>
import Button from '@/components/ui/Button.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AuthLayout from '@/layouts/auth-layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({});

function submit() {
    form.post('/email/verification-notification');
}
</script>

<template>
    <AuthLayout title="Verify email" description="Please verify your email address by clicking the link we just sent">
        <Head title="Verify email" />
        <div v-if="status === 'verification-link-sent'" class="mb-4 text-sm text-green-600">
            A new verification link has been sent to your email.
        </div>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Resend verification email
            </Button>
            <a href="/logout" class="text-center text-sm text-muted-foreground hover:text-foreground">Log out</a>
        </form>
    </AuthLayout>
</template>
