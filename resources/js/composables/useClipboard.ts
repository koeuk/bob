import { ref } from 'vue';

export function useClipboard() {
    const copiedText = ref<string | null>(null);

    async function copy(text: string): Promise<boolean> {
        if (!navigator?.clipboard) {
            console.warn('Clipboard not supported');
            return false;
        }

        try {
            await navigator.clipboard.writeText(text);
            copiedText.value = text;
            return true;
        } catch (error) {
            console.warn('Copy failed', error);
            copiedText.value = null;
            return false;
        }
    }

    return { copiedText, copy };
}
