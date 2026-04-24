<script setup lang="ts">
import DeleteUser from '@/components/delete-user.vue';
import HeadingSmall from '@/components/heading-small.vue';
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AppLayout from '@/layouts/app-layout.vue';
import SettingsLayout from '@/layouts/settings-layout.vue';
import { send as sendVerification } from '@/routes/verification';
import { update as updateProfile } from '@/routes/profile';
import type { SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{ mustVerifyEmail: boolean; status?: string }>();

const page = usePage<SharedData>();
const user = page.props.auth.user;

const form = useForm({ name: user.name, email: user.email });

function submit() {
    form.patch(updateProfile.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Profile" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Profile information" description="Update your name and email address" />
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autocomplete="name" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" required autocomplete="username" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm">
                        Your email is unverified.
                        <Link :href="sendVerification.url()" method="post" as="button" class="text-primary underline">Resend verification email</Link>
                        <span v-if="status === 'verification-link-sent'" class="ml-2 text-green-600">Sent.</span>
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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
