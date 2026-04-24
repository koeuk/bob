import { qrCode, recoveryCodes, secretKey } from '@/routes/two-factor';
import { computed, ref } from 'vue';

interface TwoFactorSetupData {
    svg: string;
    url: string;
}

interface TwoFactorSecretKey {
    secretKey: string;
}

export const OTP_MAX_LENGTH = 6;

async function fetchJson<T>(url: string): Promise<T> {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`Failed to fetch: ${response.status}`);
    return response.json();
}

export function useTwoFactorAuth() {
    const qrCodeSvg = ref<string | null>(null);
    const manualSetupKey = ref<string | null>(null);
    const recoveryCodesList = ref<string[]>([]);
    const errors = ref<string[]>([]);

    const hasSetupData = computed(() => qrCodeSvg.value !== null && manualSetupKey.value !== null);

    async function fetchQrCode() {
        try {
            const { svg } = await fetchJson<TwoFactorSetupData>(qrCode.url());
            qrCodeSvg.value = svg;
        } catch {
            errors.value = [...errors.value, 'Failed to fetch QR code'];
            qrCodeSvg.value = null;
        }
    }

    async function fetchSetupKey() {
        try {
            const { secretKey: key } = await fetchJson<TwoFactorSecretKey>(secretKey.url());
            manualSetupKey.value = key;
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
            const codes = await fetchJson<string[]>(recoveryCodes.url());
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
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        hasSetupData,
        errors,
        clearErrors,
        clearSetupData,
        fetchQrCode,
        fetchSetupKey,
        fetchSetupData,
        fetchRecoveryCodes,
    };
}
