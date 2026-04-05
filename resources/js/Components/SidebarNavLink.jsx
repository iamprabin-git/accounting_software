import { Link } from '@inertiajs/react';

export default function SidebarNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ' +
                (active
                    ? 'bg-slate-800 text-white shadow-sm'
                    : 'text-slate-300 hover:bg-slate-800/70 hover:text-white') +
                (className ? ` ${className}` : '')
            }
        >
            {children}
        </Link>
    );
}
