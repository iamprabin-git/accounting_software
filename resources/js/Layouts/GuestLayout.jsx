import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeLanguageControls from '@/Components/ThemeLanguageControls';
import { Link } from '@inertiajs/react';

const starField = Array.from({ length: 26 }, (_, i) => ({
    id: i,
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    size: 1 + Math.random() * 2.2,
    delay: `${Math.random() * 2.5}s`,
    duration: `${1.8 + Math.random() * 2.4}s`,
    opacity: 0.35 + Math.random() * 0.5,
}));

/**
 * @param {{ name: string, logo_url: string | null } | null | undefined} [branding]
 * @param {boolean} [showFallbackLogo]
 */
export default function GuestLayout({
    children,
    branding = null,
    showFallbackLogo = true,
}) {
    return (
        <div className="cbs-auth-shell relative flex min-h-screen flex-col items-center overflow-hidden px-4 pt-6 sm:justify-center sm:px-6 sm:pt-0">
            <div className="pointer-events-none absolute inset-0">
                <img
                    src="/images/auth/login-abstract.svg"
                    alt=""
                    className="h-full w-full object-cover"
                    aria-hidden
                />
                <div className="absolute inset-0 bg-white/70 dark:bg-slate-950/78" />
                <div className="absolute inset-0 bg-slate-50/72 dark:bg-slate-950/80" />
                <div className="absolute inset-0 bg-gradient-to-br from-emerald-100/30 via-transparent to-slate-200/20 dark:from-emerald-500/10 dark:via-transparent dark:to-slate-800/30" />
                <div className="absolute inset-0">
                    {starField.map((star) => (
                        <span
                            key={star.id}
                            className="absolute animate-pulse rounded-full bg-slate-200 dark:bg-emerald-200/90"
                            style={{
                                left: star.left,
                                top: star.top,
                                width: `${star.size}px`,
                                height: `${star.size}px`,
                                animationDelay: star.delay,
                                animationDuration: star.duration,
                                opacity: star.opacity,
                                boxShadow:
                                    '0 0 8px rgba(16, 185, 129, 0.35), 0 0 4px rgba(148, 163, 184, 0.45)',
                            }}
                        />
                    ))}
                </div>
            </div>
            <div className="absolute end-4 top-4 z-10 sm:end-6 sm:top-6">
                <ThemeLanguageControls />
            </div>
            <div className="relative z-10 flex flex-col items-center">
                <Link href="/" className="flex flex-col items-center">
                    {branding?.name ? (
                        <>
                            {branding.logo_url ? (
                                <img
                                    src={branding.logo_url}
                                    alt=""
                                    className="mx-auto h-20 w-auto max-h-20 max-w-[220px] object-contain"
                                />
                            ) : showFallbackLogo ? (
                                <ApplicationLogo className="h-20 w-20 fill-current text-gray-500 dark:text-muted-foreground" />
                            ) : null}
                            {branding.logo_url || showFallbackLogo ? (
                                <>
                                    <p className="mt-3 max-w-sm text-center text-lg font-semibold leading-snug text-gray-900 dark:text-foreground">
                                        {branding.name}
                                    </p>
                                    <p className="mt-1 text-center text-xs text-gray-600 dark:text-muted-foreground">
                                        Sign in to your organization
                                    </p>
                                </>
                            ) : (
                                <>
                                    <p className="max-w-sm text-center text-lg font-semibold leading-snug text-gray-900 dark:text-foreground">
                                        {branding.name}
                                    </p>
                                    <p className="mt-1 text-center text-xs text-gray-600 dark:text-muted-foreground">
                                        Sign in to your organization
                                    </p>
                                </>
                            )}
                        </>
                    ) : showFallbackLogo ? (
                        <ApplicationLogo className="h-20 w-20 fill-current text-gray-500 dark:text-muted-foreground" />
                    ) : null}
                    {!branding?.name && !showFallbackLogo ? (
                        <div className="h-2" aria-hidden />
                    ) : null}
                </Link>
            </div>

            <div className="relative z-10 cbs-auth-card mt-6 w-full overflow-hidden backdrop-blur-[2px] sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
