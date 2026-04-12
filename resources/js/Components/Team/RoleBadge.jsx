import { cn } from '@/lib/utils';

const STYLES = {
    staff: 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/80 dark:text-indigo-100',
    end_user:
        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-100',
};

const LABELS = {
    staff: 'Staff role',
    end_user: 'End user role',
};

export default function RoleBadge({ kind, className }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold uppercase tracking-wide',
                STYLES[kind] ?? 'bg-muted text-muted-foreground',
                className,
            )}
        >
            {LABELS[kind] ?? kind}
        </span>
    );
}
