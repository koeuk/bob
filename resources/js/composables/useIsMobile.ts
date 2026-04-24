import { onBeforeUnmount, onMounted, ref } from 'vue';

const MOBILE_BREAKPOINT = 768;

export function useIsMobile() {
    const isMobile = ref(false);
    let mql: MediaQueryList | null = null;

    const update = () => {
        isMobile.value = mql?.matches ?? false;
    };

    onMounted(() => {
        mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
        update();
        mql.addEventListener('change', update);
    });

    onBeforeUnmount(() => {
        mql?.removeEventListener('change', update);
    });

    return isMobile;
}
