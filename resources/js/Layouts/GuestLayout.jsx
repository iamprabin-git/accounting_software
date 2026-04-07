import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeLanguageControls from '@/Components/ThemeLanguageControls';
import { Link } from '@inertiajs/react';

/**
 * @param {{ name: string, logo_url: string | null } | null | undefined} [branding]
 */
export default function GuestLayout({ children, branding = null }) {
    return (
        <div className="cbs-auth-shell relative flex min-h-screen flex-col items-center px-4 pt-6 sm:justify-center sm:px-6 sm:pt-0">
            <div className="absolute end-4 top-4 z-10 sm:end-6 sm:top-6">
                <ThemeLanguageControls />
            </div>
            <div className="flex flex-col items-center">
                <Link href="/" className="flex flex-col items-center">
                    {branding?.name ? (
                        <>
                            {branding.logo_url ? (
                                <img
                                    src={branding.logo_url}
                                    alt=""
                                    className="mx-auto h-20 w-auto max-h-20 max-w-[220px] object-contain"
                                />
                            ) : (
                                <ApplicationLogo className="h-20 w-20 fill-current text-gray-500 dark:text-muted-foreground" />
                            )}
                            <p className="mt-3 max-w-sm text-center text-lg font-semibold leading-snug text-gray-900 dark:text-foreground">
                                {branding.name}
                            </p>
                            <p className="mt-1 text-center text-xs text-gray-600 dark:text-muted-foreground">
                                Sign in to your organization
                            </p>
                        </>
                    ) : (
                        <ApplicationLogo className="h-20 w-20 fill-current text-gray-500 dark:text-muted-foreground" />
                    )}
                </Link>
            </div>

            <div className="cbs-auth-card mt-6 w-full overflow-hidden sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
