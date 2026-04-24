import { computed, ref } from 'vue';

export const OTP_MAX_LENGTH = 6;

async function fetchJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`Failed to fetch: ${response.status}`);
    return response.json();
}

export function useTwoFactorAuth() {
    const qrCodeSvg = ref(null);
    const manualSetupKey = ref(null);
    const recoveryCodesList = ref([]);
    const errors = ref([]);

    const hasSetupData = computed(() => qrCodeSvg.value !== null && manualSetupKey.value !== null);

    async function fetchQrCode() {
        try {
            const { svg } = await fetchJson('/user/two-factor-qr-code');
            qrCodeSvg.value = svg;
        } catch {
            errors.value = [...errors.value, 'Failed to fetch QR code'];
            qrCodeSvg.value = null;
        }
    }

    async function fetchSetupKey() {
        try {
            const { secretKey } = await fetchJson('/user/two-factor-secret-key');
            manualSetupKey.value = secretKey;
        } catch {
            errors.value = [...errors.value, 'Failed to fetch a setup key'];
            manualSetupKey.value = null;
        }
    }

    function clearErrors() {
        errors.value = [];
    }

    function clearSetupData() {
        manualSetupKey.value = null;
        qrCodeSvg.value = null;
        clearErrors();
    }

    async function fetchRecoveryCodes() {
        try {
            clearErrors();
            const codes = await fetchJson('/user/two-factor-recovery-codes');
            recoveryCodesList.value = codes;
        } catch {
            errors.value = [...errors.value, 'Failed to fetch recovery codes'];
            recoveryCodesList.value = [];
        }
    }

    async function fetchSetupData() {
        try {
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch {
            qrCodeSvg.value = null;
            manualSetupKey.value = null;
        }
    }

    return {
        qrCodeSvg, manualSetupKey, recoveryCodesList, hasSetupData, errors,
        clearErrors, clearSetupData, fetchQrCode, fetchSetupKey, fetchSetupData, fetchRecoveryCodes,
    };
}
