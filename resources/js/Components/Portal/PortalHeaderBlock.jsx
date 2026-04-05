import { cn } from '@/lib/utils';

export default function PortalHeaderBlock({
    eyebrow = 'Member portal',
    title,
    description,
    actions,
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between',
            )}
        >
            <div className="min-w-0 space-y-1">
                <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">
                    {eyebrow}
                </p>
                <h2 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                    {title}
                </h2>
                {description ? (
                    <p className="max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {description}
                    </p>
                ) : null}
            </div>
            {actions ? (
                <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>
            ) : null}
        </div>
    );
}
