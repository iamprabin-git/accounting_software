import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import {
    WEEKDAY_LABELS,
    buildMonthCells,
    isWorkingDayIso,
    parseIsoDate,
} from '@/utils/calendarGrid';
import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

export default function WorkDatePicker({
    id,
    label,
    value,
    onChange,
    error,
    required = false,
    className = '',
    /** When true, holidays and weekends are selectable (e.g. teller day — operations, not GL posting dates). */
    allowNonWorkingDays = false,
}) {
    const page = usePage();
    const holidaySet = useMemo(
        () => new Set(page.props.company_holiday_dates ?? []),
        [page.props.company_holiday_dates],
    );
    const workingOverrideSet = useMemo(
        () => new Set(page.props.company_working_override_dates ?? []),
        [page.props.company_working_override_dates],
    );

    const parsed = parseIsoDate(value);
    const initialY = parsed?.y ?? new Date().getFullYear();
    const initialM = parsed?.m ?? new Date().getMonth();

    const [open, setOpen] = useState(false);
    const [viewY, setViewY] = useState(initialY);
    const [viewM, setViewM] = useState(initialM);
    const rootRef = useRef(null);

    useEffect(() => {
        if (parsed) {
            setViewY(parsed.y);
            setViewM(parsed.m);
        }
    }, [value]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }
        const onDoc = (e) => {
            if (rootRef.current && !rootRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, [open]);

    const cells = useMemo(
        () => buildMonthCells(viewY, viewM),
        [viewY, viewM],
    );

    const pick = useCallback(
        (iso) => {
            if (
                !allowNonWorkingDays &&
                !isWorkingDayIso(iso, holidaySet, workingOverrideSet)
            ) {
                return;
            }
            onChange(iso);
            setOpen(false);
        },
        [allowNonWorkingDays, holidaySet, workingOverrideSet, onChange],
    );

    const monthLabel = new Date(viewY, viewM, 1).toLocaleString(undefined, {
        month: 'long',
        year: 'numeric',
    });

    return (
        <div
            className={`relative w-full max-w-full min-w-0 ${className}`.trim()}
            ref={rootRef}
        >
            {label ? (
                <InputLabel htmlFor={id} value={label} />
            ) : null}
            <button
                id={id}
                type="button"
                aria-required={required || undefined}
                onClick={() => setOpen((o) => !o)}
                className={
                    'mt-1 flex w-full touch-manipulation items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2.5 text-left text-sm shadow-sm hover:bg-gray-50 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 ' +
                    (error ? 'border-red-300' : '')
                }
            >
                <span className={value ? 'text-gray-900' : 'text-gray-400'}>
                    {value || 'Select date'}
                </span>
                <span className="text-xs text-gray-500" aria-hidden>
                    ▾
                </span>
            </button>
            <InputError message={error} className="mt-2" />

            {open ? (
                <div
                    className="absolute left-0 z-50 mt-1 w-full max-w-[280px] rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
                    role="dialog"
                    aria-label={
                        allowNonWorkingDays
                            ? 'Choose date'
                            : 'Choose transaction date'
                    }
                >
                    <div className="mb-2 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            className="rounded p-1 text-gray-600 hover:bg-gray-100"
                            onClick={() => {
                                if (viewM === 0) {
                                    setViewM(11);
                                    setViewY((y) => y - 1);
                                } else {
                                    setViewM((m) => m - 1);
                                }
                            }}
                        >
                            ‹
                        </button>
                        <span className="text-sm font-semibold text-gray-900">
                            {monthLabel}
                        </span>
                        <button
                            type="button"
                            className="rounded p-1 text-gray-600 hover:bg-gray-100"
                            onClick={() => {
                                if (viewM === 11) {
                                    setViewM(0);
                                    setViewY((y) => y + 1);
                                } else {
                                    setViewM((m) => m + 1);
                                }
                            }}
                        >
                            ›
                        </button>
                    </div>
                    <div className="grid grid-cols-7 gap-1 text-center text-[10px] font-medium uppercase text-gray-500">
                        {WEEKDAY_LABELS.map((w) => (
                            <div key={w} className="py-1">
                                {w}
                            </div>
                        ))}
                    </div>
                    <div className="mt-1 grid grid-cols-7 gap-1">
                        {cells.map((cell, idx) => {
                            if (cell.kind === 'pad') {
                                return (
                                    <div
                                        key={`p-${idx}`}
                                        className="aspect-square"
                                    />
                                );
                            }
                            const isHol = holidaySet.has(cell.iso);
                            const working = isWorkingDayIso(
                                cell.iso,
                                holidaySet,
                                workingOverrideSet,
                            );
                            const blocked =
                                !allowNonWorkingDays && !working;
                            const selected = value === cell.iso;
                            const isWeekendOv =
                                workingOverrideSet.has(cell.iso) && !isHol;
                            return (
                                <button
                                    key={cell.iso}
                                    type="button"
                                    disabled={blocked}
                                    onClick={() => pick(cell.iso)}
                                    className={
                                        'flex aspect-square min-h-[2.25rem] touch-manipulation items-center justify-center rounded text-sm ' +
                                        (blocked
                                            ? 'cursor-not-allowed bg-gray-100 text-gray-400 line-through'
                                            : isHol
                                              ? 'bg-red-50 text-red-900 hover:bg-red-100'
                                              : isWeekendOv
                                                ? 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100'
                                                : 'text-gray-800 hover:bg-indigo-50') +
                                        (selected && !blocked
                                            ? ' ring-2 ring-indigo-500 ring-offset-1'
                                            : '')
                                    }
                                    title={
                                        allowNonWorkingDays && !working
                                            ? isHol
                                                ? 'Company holiday (allowed for teller day)'
                                                : 'Weekend (allowed for teller day)'
                                            : blocked
                                              ? isHol
                                                  ? 'Company holiday'
                                                  : 'Weekend — not a working day'
                                              : isWeekendOv
                                                ? 'Weekend marked as working'
                                                : ''
                                    }
                                >
                                    {cell.day}
                                </button>
                            );
                        })}
                    </div>
                    <p className="mt-2 text-[10px] leading-snug text-gray-500">
                        {allowNonWorkingDays
                            ? 'Any calendar date is allowed here. Cash and journal postings still require a working-day transaction date.'
                            : 'Only working days can be used for transaction dates. Manage holidays and weekend overrides under Company → Holidays.'}
                    </p>
                </div>
            ) : null}
        </div>
    );
}
