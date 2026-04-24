import { onMounted, onUnmounted, ref } from 'vue';

const prefersDark = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name, value, days = 365) => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const mediaQuery = () => (typeof window === 'undefined' ? null : window.matchMedia('(prefers-color-scheme: dark)'));

const handleSystemThemeChange = () => {
    const current = localStorage.getItem('appearance') || 'system';
    applyTheme(current);
};

export function initializeTheme() {
    const saved = localStorage.getItem('appearance') || 'system';
    applyTheme(saved);
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref('system');

    function updateAppearance(mode) {
        appearance.value = mode;
        localStorage.setItem('appearance', mode);
        setCookie('appearance', mode);
        applyTheme(mode);
    }

    onMounted(() => {
        const saved = localStorage.getItem('appearance');
        updateAppearance(saved || 'system');
    });

    onUnmounted(() => {
        mediaQuery()?.removeEventListener('change', handleSystemThemeChange);
    });

    return { appearance, updateAppearance };
}
