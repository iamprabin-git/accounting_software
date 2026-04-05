import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

function IconAccounts({ active }) {
    return (
        <svg
            className={cn(
                'h-6 w-6',
                active
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-slate-400 dark:text-slate-500',
            )}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.75}
                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m0 0h-9.75m9.75 0v-.375c0-.621-.504-1.125-1.125-1.125h-9.75c-.621 0-1.125.504-1.125 1.125v.375m9.75 0H3.375m0 0h-.375c-.621 0-1.125-.504-1.125-1.125V9.75m12 0v9.75"
            />
        </svg>
    );
}

function IconPassbook({ active }) {
    return (
        <svg
            className={cn(
                'h-6 w-6',
                active
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-slate-400 dark:text-slate-500',
            )}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.75}
                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
            />
        </svg>
    );
}

function IconMessages({ active }) {
    return (
        <svg
            className={cn(
                'h-6 w-6',
                active
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-slate-400 dark:text-slate-500',
            )}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.75}
                d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"
            />
        </svg>
    );
}

export default function PortalBottomNav() {
    const activeHome = route().current('portal.home');
    const activePassbook = route().current('portal.passbook');
    const activeMessages =
        route().current('portal.messages') ||
        route().current('portal.messages.store');

    const items = [
        {
            key: 'home',
            href: route('portal.home'),
            active: activeHome,
            label: 'Accounts',
            Icon: IconAccounts,
        },
        {
            key: 'passbook',
            href: route('portal.passbook'),
            active: activePassbook,
            label: 'Passbook',
            Icon: IconPassbook,
        },
        {
            key: 'messages',
            href: route('portal.messages'),
            active: activeMessages,
            label: 'Messages',
            Icon: IconMessages,
        },
    ];

    return (
        <nav
            className="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200/90 bg-white/95 pb-[env(safe-area-inset-bottom,0px)] shadow-[0_-4px_24px_-8px_rgba(15,23,42,0.12)] backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95 md:hidden print:hidden"
            aria-label="Member portal navigation"
        >
            <div className="mx-auto flex max-w-lg items-stretch justify-around px-2 pt-1">
                {items.map((item) => (
                    <Link
                        key={item.key}
                        href={item.href}
                        className={cn(
                            'flex min-h-[3.25rem] min-w-[4.5rem] flex-1 flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-1 text-[10px] font-semibold transition-colors',
                            item.active
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                        )}
                    >
                        <item.Icon active={item.active} />
                        <span>{item.label}</span>
                    </Link>
                ))}
            </div>
        </nav>
    );
}
