import CompanyWorkspaceSidebar from '@/Components/CompanyWorkspaceSidebar';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    WEEKDAY_LABELS,
    buildMonthCells,
    isWeekendIso,
    isWorkingDayIso,
} from '@/utils/calendarGrid';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function Index({
    holidays = [],
    working_overrides: workingOverrides = [],
    can_manage_holidays = false,
}) {
    const { t } = useTranslation();
    const page = usePage();
    const user = page.props.auth?.user ?? {};
    const currentCompanyId = page.props.current_company_id;

    const q =
        user.role === 'admin' && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const [viewY, setViewY] = useState(() => new Date().getFullYear());
    const [viewM, setViewM] = useState(() => new Date().getMonth());
    const [selectedIso, setSelectedIso] = useState(null);

    const byDate = useMemo(() => {
        const m = new Map();
        holidays.forEach((h) => {
            m.set(h.holiday_date, h);
        });
        return m;
    }, [holidays]);

    const overrideByDate = useMemo(() => {
        const m = new Map();
        workingOverrides.forEach((o) => {
            m.set(o.work_date, o);
        });
        return m;
    }, [workingOverrides]);

    const holidaySet = useMemo(() => new Set(byDate.keys()), [byDate]);
    const workingOverrideSet = useMemo(
        () => new Set(overrideByDate.keys()),
        [overrideByDate],
    );

    const cells = useMemo(
        () => buildMonthCells(viewY, viewM),
        [viewY, viewM],
    );

    const monthLabel = new Date(viewY, viewM, 1).toLocaleString(undefined, {
        month: 'long',
        year: 'numeric',
    });

    const onSelectDay = (iso) => {
        setSelectedIso(iso);
    };

    const setAsHoliday = () => {
        if (!can_manage_holidays || !selectedIso) {
            return;
        }
        const name = window.prompt('Holiday name (optional):', '') ?? '';
        router.post(
            route('company.holidays.store', q),
            { holiday_date: selectedIso, name },
            { preserveScroll: true },
        );
    };

    const setAsWorkingDay = () => {
        if (!can_manage_holidays || !selectedIso) {
            return;
        }
        router.post(
            route('company.working-day-overrides.store', q),
            { work_date: selectedIso },
            { preserveScroll: true },
        );
    };

    const removeHoliday = () => {
        if (!can_manage_holidays || !selectedIso) {
            return;
        }
        const row = byDate.get(selectedIso);
        if (!row) {
            return;
        }
        if (
            window.confirm(
                `Remove holiday${row.name ? ` “${row.name}”` : ''} on ${selectedIso}?`,
            )
        ) {
            router.delete(
                route('company.holidays.destroy', {
                    holiday: row.id,
                    ...q,
                }),
                { preserveScroll: true },
            );
        }
    };

    const removeWeekendOverride = () => {
        if (!can_manage_holidays || !selectedIso) {
            return;
        }
        const row = overrideByDate.get(selectedIso);
        if (!row) {
            return;
        }
        if (
            window.confirm(
                `Remove weekend working override for ${selectedIso}?`,
            )
        ) {
            router.delete(
                route('company.working-day-overrides.destroy', {
                    override: row.id,
                    ...q,
                }),
                { preserveScroll: true },
            );
        }
    };

    const selectedIsHol = selectedIso ? byDate.has(selectedIso) : false;
    const selectedIsWeekend = selectedIso ? isWeekendIso(selectedIso) : false;
    const selectedHasOverride = selectedIso
        ? overrideByDate.has(selectedIso)
        : false;
    const selectedWorking =
        selectedIso &&
        isWorkingDayIso(selectedIso, holidaySet, workingOverrideSet);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {t('nav.companyHolidays')}
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            {t('companyWorkspace.workingCalendarPageSubtitle')}
                        </p>
                    </div>
                    <Link
                        href={route('company.configuration.edit', q)}
                        className="self-start text-sm text-gray-600 underline hover:text-gray-900 sm:self-center"
                    >
                        {t('companyWorkspace.linkBackToConfiguration')}
                    </Link>
                </div>
            }
        >
            <Head title={t('nav.companyHolidays')} />

            <div className="py-8 sm:py-12">
                <div className="mx-auto flex w-full min-w-0 max-w-6xl flex-col gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:gap-10 lg:px-8">
                    <CompanyWorkspaceSidebar />
                    <div className="min-w-0 flex-1">
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-6 lg:p-8">
                            <section
                                aria-labelledby="working-cal-intro"
                                className="border-b border-gray-100 pb-6"
                            >
                                <h3
                                    id="working-cal-intro"
                                    className="text-sm font-semibold text-gray-900"
                                >
                                    {t('companyWorkspace.workingCalendarSectionCalendar')}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {can_manage_holidays
                                        ? 'Select a date, then use Set as a holiday or Set as a working day. Holidays show as a bar at the top of the cell. Postings are only allowed on working days (weekdays, or weekends you mark as working).'
                                        : 'View only. Only an organization owner or platform admin can change the calendar.'}
                                </p>
                            </section>
                            <div className="mx-auto mt-8 w-full max-w-md min-w-0 rounded-xl border border-gray-200 bg-gray-50/80 p-3 sm:p-4">
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        className="min-h-10 min-w-10 touch-manipulation rounded p-2 text-gray-700 hover:bg-white"
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
                                        className="min-h-10 min-w-10 touch-manipulation rounded p-2 text-gray-700 hover:bg-white"
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
                                <div className="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase text-gray-500">
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
                                        const row = byDate.get(cell.iso);
                                        const isHol = Boolean(row);
                                        const hasOv = overrideByDate.has(
                                            cell.iso,
                                        );
                                        const selected = cell.iso === selectedIso;
                                        return (
                                            <button
                                                key={cell.iso}
                                                type="button"
                                                disabled={!can_manage_holidays}
                                                onClick={() =>
                                                    onSelectDay(cell.iso)
                                                }
                                                className={
                                                    'relative flex aspect-square min-h-[2.25rem] touch-manipulation flex-col overflow-hidden rounded text-sm font-medium transition ring-1 ' +
                                                    (selected
                                                        ? 'ring-2 ring-indigo-500 ring-offset-1 '
                                                        : 'ring-gray-200 ') +
                                                    (isHol
                                                        ? 'bg-red-50 text-red-950 hover:bg-red-100'
                                                        : hasOv
                                                          ? 'bg-emerald-50 text-emerald-950 hover:bg-emerald-100'
                                                          : 'bg-white text-gray-800 hover:bg-indigo-50') +
                                                    (!can_manage_holidays
                                                        ? ' cursor-default opacity-90'
                                                        : '')
                                                }
                                            >
                                                {isHol ? (
                                                    <span className="w-full shrink-0 bg-red-600 py-0.5 text-center text-[8px] font-bold uppercase leading-none text-white">
                                                        {row?.name
                                                            ? row.name.slice(
                                                                  0,
                                                                  10,
                                                              )
                                                            : 'Holiday'}
                                                    </span>
                                                ) : null}
                                                <span className="flex flex-1 items-center justify-center">
                                                    {cell.day}
                                                </span>
                                                {hasOv && !isHol ? (
                                                    <span
                                                        className="absolute bottom-0.5 right-0.5 h-1.5 w-1.5 rounded-full bg-emerald-600"
                                                        title="Weekend working"
                                                        aria-hidden
                                                    />
                                                ) : null}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {can_manage_holidays ? (
                                <section
                                    aria-labelledby="working-cal-actions"
                                    className="mx-auto mt-8 max-w-md space-y-3 rounded-lg border border-gray-200 bg-gray-50/60 p-4"
                                >
                                    <h3
                                        id="working-cal-actions"
                                        className="text-sm font-semibold text-gray-900"
                                    >
                                        {t('companyWorkspace.workingCalendarSectionActions')}
                                    </h3>
                                    <div className="text-sm text-gray-700">
                                        <span className="font-medium text-gray-900">
                                            Date:{' '}
                                        </span>
                                        {selectedIso ?? '—'}
                                        {selectedIso ? (
                                            <span className="ml-2 text-gray-500">
                                                {selectedWorking
                                                    ? '(working day)'
                                                    : '(not a working day)'}
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                        <button
                                            type="button"
                                            disabled={!selectedIso}
                                            onClick={setAsHoliday}
                                            className="w-full touch-manipulation rounded-md bg-red-700 px-3 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                        >
                                            Set as a holiday
                                        </button>
                                        <button
                                            type="button"
                                            disabled={!selectedIso}
                                            onClick={setAsWorkingDay}
                                            className="w-full touch-manipulation rounded-md bg-emerald-700 px-3 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                        >
                                            Set as a working day
                                        </button>
                                    </div>
                                    <p className="text-xs text-gray-500">
                                        Set as a working day clears a holiday on
                                        that date. On weekends it also allows
                                        posting; on weekdays it only removes a
                                        holiday.
                                    </p>
                                    {selectedIsHol ? (
                                        <button
                                            type="button"
                                            onClick={removeHoliday}
                                            className="text-sm text-red-700 underline hover:text-red-900"
                                        >
                                            Remove holiday
                                        </button>
                                    ) : null}
                                    {selectedIsWeekend && selectedHasOverride ? (
                                        <button
                                            type="button"
                                            onClick={removeWeekendOverride}
                                            className="text-sm text-emerald-800 underline hover:text-emerald-950"
                                        >
                                            Remove weekend working override
                                        </button>
                                    ) : null}
                                </section>
                            ) : null}

                            <section
                                aria-labelledby="working-cal-lists"
                                className="mt-10 border-t border-gray-100 pt-8"
                            >
                                <h3
                                    id="working-cal-lists"
                                    className="text-sm font-semibold text-gray-900"
                                >
                                    {t('companyWorkspace.workingCalendarSectionLists')}
                                </h3>
                            <ul className="mt-4 space-y-1 text-sm text-gray-700">
                                <li className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Holidays
                                </li>
                                {holidays.length === 0 ? (
                                    <li className="text-gray-500">
                                        No holidays in this organization.
                                    </li>
                                ) : (
                                    holidays.map((h) => (
                                        <li key={h.id}>
                                            <span className="font-mono text-xs">
                                                {h.holiday_date}
                                            </span>
                                            {h.name ? (
                                                <span className="ml-2">
                                                    — {h.name}
                                                </span>
                                            ) : null}
                                        </li>
                                    ))
                                )}
                            </ul>
                            {workingOverrides.length > 0 ? (
                                <ul className="mt-6 space-y-1 text-sm text-gray-700">
                                    <li className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Weekend working overrides
                                    </li>
                                    {workingOverrides.map((o) => (
                                        <li key={o.id}>
                                            <span className="font-mono text-xs">
                                                {o.work_date}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
