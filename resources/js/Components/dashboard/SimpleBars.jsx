import { cn } from '@/lib/utils';

/**
 * Normalized horizontal bars — values scaled to the largest in the set.
 */
export function HorizontalBarGroup({
    title,
    items,
    className,
    barClassName,
    valueClassName,
}) {
    if (!items?.length) {
        return null;
    }
    const maxVal = Math.max(
        ...items.map((i) => Math.abs(Number(i.value) || 0)),
        1,
    );

    return (
        <div className={cn('space-y-3', className)}>
            {title ? (
                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {title}
                </p>
            ) : null}
            <ul className="space-y-2.5">
                {items.map((row) => {
                    const raw = Number(row.value) || 0;
                    const w = Math.min(100, (Math.abs(raw) / maxVal) * 100);
                    return (
                        <li key={row.key} className="space-y-1">
                            <div className="flex items-baseline justify-between gap-2 text-xs">
                                <span className="truncate font-medium text-foreground">
                                    {row.label}
                                </span>
                                <span
                                    className={cn(
                                        'shrink-0 tabular-nums text-muted-foreground',
                                        valueClassName,
                                    )}
                                >
                                    {row.display ?? String(row.value)}
                                </span>
                            </div>
                            <div
                                className="h-2 w-full overflow-hidden rounded-full bg-muted"
                                role="presentation"
                            >
                                <div
                                    className={cn(
                                        'h-full rounded-full transition-all duration-500 ease-out',
                                        row.tone === 'warning' &&
                                            'bg-amber-500 dark:bg-amber-600',
                                        row.tone === 'danger' &&
                                            'bg-red-500 dark:bg-red-600',
                                        row.tone === 'success' &&
                                            'bg-emerald-500 dark:bg-emerald-600',
                                        !row.tone &&
                                            'bg-primary/85 dark:bg-primary/70',
                                        barClassName,
                                    )}
                                    style={{ width: `${w}%` }}
                                />
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

function normalizedRatioHeight(row) {
    if (row.value === null || row.value === undefined || Number.isNaN(row.value)) {
        return 0;
    }
    if (row.format === 'percent') {
        return Math.min(1, Math.abs(row.value));
    }
    return Math.min(1, Math.abs(row.value) / 4);
}

/**
 * Mini line chart for ratio snapshot (ramp illustrates level vs baseline; not historical data).
 */
export function RatioLineSpark({ row }) {
    const yEnd = normalizedRatioHeight(row);
    const negative =
        row.value !== null &&
        row.value !== undefined &&
        !Number.isNaN(row.value) &&
        row.format === 'percent' &&
        row.value < 0;
    const n = 7;
    const pts = [];
    for (let i = 0; i < n; i++) {
        const t = i / (n - 1);
        const x = 4 + t * 112;
        const y = 36 - yEnd * 30 * t;
        pts.push({ x, y });
    }
    const lineD = pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
    const areaD = `${lineD} L ${pts[pts.length - 1].x} 38 L ${pts[0].x} 38 Z`;

    if (yEnd === 0) {
        return (
            <div
                className="mt-2 flex h-10 w-full items-center rounded-md border border-dashed border-muted-foreground/25 bg-muted/30 px-2"
                aria-hidden
            >
                <span className="text-[10px] text-muted-foreground">
                    No value to plot
                </span>
            </div>
        );
    }

    return (
        <svg
            viewBox="0 0 120 40"
            className={cn(
                'mt-2 h-11 w-full overflow-visible',
                negative ? 'text-rose-600 dark:text-rose-500' : 'text-sky-600 dark:text-sky-500',
            )}
            preserveAspectRatio="none"
            role="img"
            aria-hidden
        >
            <path
                d={areaD}
                fill="currentColor"
                fillOpacity={0.12}
                className="transition-opacity"
            />
            <path
                d={lineD}
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                vectorEffect="non-scaling-stroke"
            />
            <circle
                cx={pts[pts.length - 1].x}
                cy={pts[pts.length - 1].y}
                r="3"
                fill="currentColor"
                className="opacity-90"
            />
        </svg>
    );
}

/**
 * Debit vs credit comparison (trial balance style).
 */
export function DebitCreditBars({ debitCents, creditCents }) {
    const d = Math.abs(Number(debitCents) || 0);
    const c = Math.abs(Number(creditCents) || 0);
    const max = Math.max(d, c, 1);
    return (
        <div className="mt-3 grid gap-3 sm:grid-cols-2">
            <div className="space-y-1">
                <div className="flex justify-between text-xs text-muted-foreground">
                    <span>Debits</span>
                    <span className="tabular-nums">{d.toLocaleString()} c</span>
                </div>
                <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-indigo-500/90 dark:bg-indigo-400/80"
                        style={{ width: `${(d / max) * 100}%` }}
                    />
                </div>
            </div>
            <div className="space-y-1">
                <div className="flex justify-between text-xs text-muted-foreground">
                    <span>Credits</span>
                    <span className="tabular-nums">{c.toLocaleString()} c</span>
                </div>
                <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-violet-500/90 dark:bg-violet-400/80"
                        style={{ width: `${(c / max) * 100}%` }}
                    />
                </div>
            </div>
        </div>
    );
}
