export function useMobileNavigation() {
    return () => {
        document.body.style.removeProperty('pointer-events');
    };
}
