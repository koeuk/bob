<script setup>
import HeadingSmall from '@/components/heading-small.vue';
import Button from '@/components/ui/Button.vue';
import AppLayout from '@/layouts/app-layout.vue';
import SettingsLayout from '@/layouts/settings-layout.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { Head, router } from '@inertiajs/vue3';

defineProps({ twoFactorEnabled: Boolean, confirmsTwoFactorAuthentication: Boolean });

const { qrCodeSvg, manualSetupKey, recoveryCodesList, fetchSetupData, fetchRecoveryCodes, clearSetupData } = useTwoFactorAuth();

function handleEnable() {
    router.post('/user/two-factor-authentication', {}, { onSuccess: () => fetchSetupData() });
}

function handleDisable() {
    router.delete('/user/two-factor-authentication', { onSuccess: () => clearSetupData() });
}
</script>

<template>
    <Head title="Two-Factor Auth" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Two-factor authentication" description="Add an extra layer of security to your account" />

                <div v-if="!twoFactorEnabled">
                    <p class="text-sm text-muted-foreground mb-4">You have not enabled two-factor authentication yet.</p>
                    <Button @click="handleEnable">Enable 2FA</Button>
                </div>

                <div v-else class="space-y-4">
                    <p class="text-sm text-muted-foreground">Two-factor authentication is enabled.</p>

                    <div v-if="qrCodeSvg" v-html="qrCodeSvg" class="max-w-[200px]" />
                    <div v-if="manualSetupKey" class="font-mono text-sm">Setup key: {{ manualSetupKey }}</div>

                    <div class="flex gap-3">
                        <Button variant="outline" @click="fetchRecoveryCodes">Show recovery codes</Button>
                        <Button variant="destructive" @click="handleDisable">Disable 2FA</Button>
                    </div>

                    <ul v-if="recoveryCodesList.length" class="grid grid-cols-2 gap-1 rounded-md bg-muted p-3 font-mono text-sm">
                        <li v-for="code in recoveryCodesList" :key="code">{{ code }}</li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
