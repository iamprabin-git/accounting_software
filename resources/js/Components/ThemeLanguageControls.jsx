import { useAppPreferences } from '@/contexts/AppPreferenceContext';
import { useTranslation } from 'react-i18next';
import { Moon, Sun } from 'lucide-react';

export default function ThemeLanguageControls({ className = '' }) {
    const { t } = useTranslation();
    const { theme, toggleTheme } = useAppPreferences();

    return (
        <div className={`flex flex-wrap items-center gap-2 ${className}`}>
            <button
                type="button"
                onClick={toggleTheme}
                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-input bg-background text-foreground shadow-sm transition hover:bg-accent"
                title={theme === 'dark' ? t('layout.themeLight') : t('layout.themeDark')}
                aria-label={theme === 'dark' ? t('layout.themeLight') : t('layout.themeDark')}
            >
                {theme === 'dark' ? (
                    <Sun className="h-4 w-4" aria-hidden />
                ) : (
                    <Moon className="h-4 w-4" aria-hidden />
                )}
            </button>
        </div>
    );
}
