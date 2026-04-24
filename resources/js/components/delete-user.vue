<script setup lang="ts">
import HeadingSmall from '@/components/heading-small.vue';
import InputError from '@/components/input-error.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DialogContent from '@/components/ui/DialogContent.vue';
import DialogDescription from '@/components/ui/DialogDescription.vue';
import DialogFooter from '@/components/ui/DialogFooter.vue';
import DialogHeader from '@/components/ui/DialogHeader.vue';
import DialogTitle from '@/components/ui/DialogTitle.vue';
import DialogTrigger from '@/components/ui/DialogTrigger.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { destroy as destroyProfile } from '@/routes/profile';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({ password: '' });
const open = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

function submit() {
    form.delete(destroyProfile.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Delete account" description="Delete your account and all of its resources" />

        <div class="space-y-4 rounded-lg border border-destructive/50 bg-destructive/10 p-4">
            <div class="relative space-y-0.5 text-destructive">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>

            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button variant="destructive">Delete account</Button>
                </DialogTrigger>
                <DialogContent>
                    <form @submit.prevent="submit">
                        <DialogHeader>
                            <DialogTitle>Are you sure you want to delete your account?</DialogTitle>
                            <DialogDescription>
                                Once your account is deleted, all of its resources and data will also be permanently deleted.
                                Please enter your password to confirm you would like to permanently delete your account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="mt-4 space-y-2">
                            <Label for="password" class="sr-only">Password</Label>
                            <Input id="password" ref="passwordInput" v-model="form.password" type="password" placeholder="Password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter class="mt-6 gap-2">
                            <Button variant="secondary" type="button" @click="open = false">Cancel</Button>
                            <Button variant="destructive" type="submit" :disabled="form.processing">Delete account</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
