import i18n from '@/i18n';
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const STORAGE_THEME = 'app_theme';

const AppPreferenceContext = createContext(null);

function readStoredTheme() {
    try {
        const v = localStorage.getItem(STORAGE_THEME);
        if (v === 'dark' || v === 'light') {
            return v;
        }
    } catch {
        //
    }
    if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

function applyDomTheme(mode) {
    const root = document.documentElement;
    if (mode === 'dark') {
        root.classList.add('dark');
    } else {
        root.classList.remove('dark');
    }
}

export function AppPreferenceProvider({ children }) {
    const [theme, setThemeState] = useState(() =>
        typeof window !== 'undefined' ? readStoredTheme() : 'light',
    );

    const setTheme = useCallback((mode) => {
        const next = mode === 'dark' ? 'dark' : 'light';
        try {
            localStorage.setItem(STORAGE_THEME, next);
        } catch {
            //
        }
        applyDomTheme(next);
        setThemeState(next);
    }, []);

    useEffect(() => {
        applyDomTheme(theme);
        document.documentElement.lang = 'en';
        void i18n.changeLanguage('en');
    }, [theme]);

    const value = useMemo(
        () => ({
            theme,
            setTheme,
            toggleTheme: () => setTheme(theme === 'dark' ? 'light' : 'dark'),
        }),
        [theme, setTheme],
    );

    return (
        <AppPreferenceContext.Provider value={value}>
            {children}
        </AppPreferenceContext.Provider>
    );
}

export function useAppPreferences() {
    const ctx = useContext(AppPreferenceContext);
    if (!ctx) {
        throw new Error('useAppPreferences must be used within AppPreferenceProvider');
    }
    return ctx;
}
