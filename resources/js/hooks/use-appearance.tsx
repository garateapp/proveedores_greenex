import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const FIXED_APPEARANCE: Appearance = 'light';

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = () => {
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

export function initializeTheme() {
    localStorage.setItem('appearance', FIXED_APPEARANCE);
    setCookie('appearance', FIXED_APPEARANCE);
    applyTheme();
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>(FIXED_APPEARANCE);

    const updateAppearance = useCallback(() => {
        setAppearance(FIXED_APPEARANCE);

        localStorage.setItem('appearance', FIXED_APPEARANCE);
        setCookie('appearance', FIXED_APPEARANCE);
        applyTheme();
    }, []);

    useEffect(() => {
        updateAppearance();
    }, [updateAppearance]);

    return { appearance, updateAppearance } as const;
}
