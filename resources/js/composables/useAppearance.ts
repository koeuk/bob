import { onMounted, onUnmounted, ref } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

const prefersDark = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (appearance: Appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const mediaQuery = () => (typeof window === 'undefined' ? null : window.matchMedia('(prefers-color-scheme: dark)'));

const handleSystemThemeChange = () => {
    const current = (localStorage.getItem('appearance') as Appearance) || 'system';
    applyTheme(current);
};

export function initializeTheme() {
    const saved = (localStorage.getItem('appearance') as Appearance) || 'system';
    applyTheme(saved);
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<Appearance>('system');

    function updateAppearance(mode: Appearance) {
        appearance.value = mode;
        localStorage.setItem('appearance', mode);
        setCookie('appearance', mode);
        applyTheme(mode);
    }

    onMounted(() => {
        const saved = localStorage.getItem('appearance') as Appearance | null;
        updateAppearance(saved || 'system');
    });

    onUnmounted(() => {
        mediaQuery()?.removeEventListener('change', handleSystemThemeChange);
    });

    return { appearance, updateAppearance };
}
