import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { moneyFromCents } from '@/utils/money';
import { Link } from '@inertiajs/react';

/**
 * @param {'loan'|'savings'} variant
 */
export default function PortalAccountCard({
    variant,
    title,
    accountNumber,
    principalCents,
    isOperational,
    href,
    actionLabel = 'Payment & details',
}) {
    const accent =
        variant === 'loan'
            ? 'border-l-blue-600 dark:border-l-blue-500'
            : 'border-l-emerald-600 dark:border-l-emerald-500';

    return (
        <div
            className={cn(
                'flex flex-col gap-4 rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:flex-row sm:items-center sm:justify-between sm:p-5',
                'border-l-4',
                accent,
            )}
        >
            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span
                        className={cn(
                            'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                            variant === 'loan'
                                ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200'
                                : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
                        )}
                    >
                        {variant === 'loan' ? 'Loan' : 'Savings'}
                    </span>
                    {!isOperational ? (
                        <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            Pending
                        </span>
                    ) : null}
                </div>
                <p className="text-base font-semibold text-slate-900 dark:text-white">
                    {title}
                </p>
                <p className="font-mono text-sm text-slate-600 dark:text-slate-400">
                    {accountNumber}
                </p>
                <p className="text-sm tabular-nums text-slate-700 dark:text-slate-300">
                    <span className="font-medium text-slate-500 dark:text-slate-400">
                        Balance{' '}
                    </span>
                    <span className="text-lg font-semibold text-slate-900 dark:text-white">
                        {moneyFromCents(principalCents)}
                    </span>
                </p>
            </div>
            <div className="shrink-0 sm:ps-4">
                <Button
                    className="w-full min-[400px]:w-auto"
                    variant="secondary"
                    asChild
                >
                    <Link href={href}>{actionLabel}</Link>
                </Button>
            </div>
        </div>
    );
}
