<template>
    <Head title="Profile" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Profile information" description="Update your name, email address and profile photo" />
                <form class="space-y-6" @submit.prevent="submit">
                    <!-- Avatar -->
                    <div class="flex items-center gap-5">
                        <div class="group relative size-20 shrink-0">
                            <div class="flex size-20 items-center justify-center overflow-hidden rounded-full bg-muted text-xl font-semibold">
                                <img v-if="avatarSrc" :src="avatarSrc" class="size-20 object-cover" alt="Avatar" />
                                <template v-else>{{ initials(user.name) }}</template>
                            </div>
                            <label
                                class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                                title="Change photo"
                            >
                                <Camera class="size-5 text-white" />
                                <input ref="fileInput" type="file" accept="image/*" class="sr-only" @change="onFileChange" />
                            </label>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium">Profile photo</p>
                            <p class="text-xs text-muted-foreground">JPG, PNG or GIF · max 2 MB</p>
                            <button
                                v-if="avatarSrc"
                                type="button"
                                class="text-xs text-destructive hover:underline"
                                @click="removeAvatar"
                            >
                                Remove photo
                            </button>
                        </div>
                    </div>

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
                        <Link href="/email/verification-notification" method="post" as="button" class="text-primary underline">Resend verification email</Link>
                        <span v-if="status === 'verification-link-sent'" class="ml-2 text-moss">Sent.</span>
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

<script setup>
import DeleteUser from '@/components/delete-user.vue';
import HeadingSmall from '@/components/heading-small.vue';
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AppLayout from '@/layouts/app-layout.vue';
import SettingsLayout from '@/layouts/settings-layout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({ mustVerifyEmail: Boolean, status: String });

const page = usePage();
// Inertia replaces page.props wholesale on every response, so capturing the
// user object once would render stale values after a save.
const user = computed(() => page.props.auth.user);

const fileInput = ref(null);
const avatarPreview = ref(null);
const avatarRemoved = ref(false);

// Honour a staged removal so the trash action gives immediate feedback
// instead of falling back to the still-stored photo.
const avatarSrc = computed(() => {
    if (avatarRemoved.value) return null;
    return avatarPreview.value ?? user.value.avatar ?? null;
});

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    avatar: null,
    remove_avatar: false,
});

const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.avatar = file;
    form.remove_avatar = false;
    avatarRemoved.value = false;
    avatarPreview.value = URL.createObjectURL(file);
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    avatarRemoved.value = true;
    avatarPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

function submit() {
    form.patch('/settings/profile', { preserveScroll: true, forceFormData: true });
}
</script>
