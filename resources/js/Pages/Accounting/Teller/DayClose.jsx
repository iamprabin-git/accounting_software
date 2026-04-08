import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export default function DayClose({
    recentCloses,
    openDay,
    selectedDate,
    reportLinks,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const startForm = useForm({
        close_date: selectedDate || new Date().toISOString().slice(0, 10),
        vault_opening_cash: '',
        memo: '',
        company_id: isAdmin ? currentCompanyId : '',
    });

    const endForm = useForm({
        close_date: selectedDate || new Date().toISOString().slice(0, 10),
        counted_cash: '',
        system_cash: '',
        vault_returned_cash: '',
        memo: '',
        company_id: isAdmin ? currentCompanyId : '',
    });

    const dayOpen = Boolean(openDay);
    const selected = selectedDate || new Date().toISOString().slice(0, 10);

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            startForm.setData('company_id', currentCompanyId);
            endForm.setData('company_id', currentCompanyId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sync company picker only
    }, [currentCompanyId, isAdmin]);

    useEffect(() => {
        startForm.setData('close_date', selected);
        endForm.setData('close_date', selected);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- keep forms on selected business date
    }, [selected]);

    const submitStart = (e) => {
        e.preventDefault();
        startForm.post(route('teller.day-close.start', query));
    };

    const submitEnd = (e) => {
        e.preventDefault();
        endForm.post(route('teller.day-close.end', query));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            Teller day close
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Record opening float, physical cash count, and optional
                            expected balance for the day.
                        </p>
                    </div>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="teller.day-close.create"
                            routeParams={{}}
                            query={{}}
                        />
                    )}
                </div>
            }
        >
            <Head title="Teller day close" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-8 sm:px-6 lg:px-8">
                    <div className="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                        <h3 className="text-base font-semibold text-indigo-900">
                            1) Select business date
                        </h3>
                        <p className="text-sm text-indigo-800">
                            Workflow: choose date -&gt; Start day -&gt; run
                            transactions -&gt; End day. Before start, all operations
                            stay disabled except Start day.
                        </p>
                        <div className="max-w-xs">
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border-indigo-300 text-sm"
                                value={selected}
                                onChange={(e) =>
                                    router.get(
                                        route('teller.day-close.create'),
                                        {
                                            ...(isAdmin && currentCompanyId
                                                ? { company_id: currentCompanyId }
                                                : {}),
                                            date: e.target.value,
                                        },
                                        { preserveState: true, replace: true },
                                    )
                                }
                            />
                        </div>
                    </div>

                    {!openDay ? (
                        <form
                            onSubmit={submitStart}
                            className="space-y-4 rounded-lg bg-white p-6 shadow"
                        >
                            {isAdmin && (
                                <input type="hidden" name="company_id" value={startForm.data.company_id} />
                            )}
                            <h3 className="text-base font-semibold text-gray-900">
                                Start of day
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Date
                                    </label>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={startForm.data.close_date}
                                        onChange={(e) =>
                                            startForm.setData('close_date', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={startForm.errors.close_date} className="mt-1" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Cash received from vault
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={startForm.data.vault_opening_cash}
                                        onChange={(e) =>
                                            startForm.setData('vault_opening_cash', e.target.value)
                                        }
                                        placeholder="0.00"
                                        required
                                    />
                                    <InputError message={startForm.errors.vault_opening_cash} className="mt-1" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Memo
                                </label>
                                <input
                                    type="text"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={startForm.data.memo}
                                    onChange={(e) => startForm.setData('memo', e.target.value)}
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={startForm.processing}
                                className="rounded-md bg-indigo-700 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 disabled:opacity-50"
                            >
                                Start day
                            </button>
                        </form>
                    ) : (
                        <form
                            onSubmit={submitEnd}
                            className="space-y-4 rounded-lg border border-emerald-200 bg-emerald-50 p-6 shadow"
                        >
                            {isAdmin && (
                                <input type="hidden" name="company_id" value={endForm.data.company_id} />
                            )}
                            <h3 className="text-base font-semibold text-emerald-900">
                                End of day
                            </h3>
                            <p className="text-sm text-emerald-800">
                                Open day for {openDay.close_date}. Vault opening: {money(openDay.vault_opening_cash_cents)}
                            </p>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Date
                                    </label>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={endForm.data.close_date}
                                        onChange={(e) =>
                                            endForm.setData('close_date', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={endForm.errors.close_date} className="mt-1" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Counted cash
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={endForm.data.counted_cash}
                                        onChange={(e) =>
                                            endForm.setData('counted_cash', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={endForm.errors.counted_cash} className="mt-1" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        System cash
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={endForm.data.system_cash}
                                        onChange={(e) =>
                                            endForm.setData('system_cash', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={endForm.errors.system_cash} className="mt-1" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Cash returned to vault
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="decimal"
                                        className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                        value={endForm.data.vault_returned_cash}
                                        onChange={(e) =>
                                            endForm.setData('vault_returned_cash', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={endForm.errors.vault_returned_cash} className="mt-1" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Memo
                                </label>
                                <input
                                    type="text"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={endForm.data.memo}
                                    onChange={(e) => endForm.setData('memo', e.target.value)}
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={endForm.processing}
                                className="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600 disabled:opacity-50"
                            >
                                End day (zero error required)
                            </button>
                        </form>
                    )}

                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow">
                        <h3 className="text-base font-semibold text-slate-900">
                            2) Operations and reports
                        </h3>
                        <p className="mt-1 text-sm text-slate-600">
                            Operations unlock only after Start day. End-of-day reports are available after End day.
                        </p>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2">
                            <Link
                                href={route('journals.create-cash-in', {
                                    ...query,
                                    date: selected,
                                })}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    dayOpen
                                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Cash receive
                            </Link>
                            <Link
                                href={route('journals.create-cash-out', {
                                    ...query,
                                    date: selected,
                                })}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    dayOpen
                                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Cash payment
                            </Link>
                            <Link
                                href={reportLinks?.trial_balance ?? '#'}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    !dayOpen
                                        ? 'border-indigo-300 bg-indigo-50 text-indigo-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Start-of-day Trial balance
                            </Link>
                            <Link
                                href={reportLinks?.profit_loss ?? '#'}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    !dayOpen
                                        ? 'border-indigo-300 bg-indigo-50 text-indigo-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Start-of-day Profit &amp; loss
                            </Link>
                            <Link
                                href={reportLinks?.balance_sheet ?? '#'}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    !dayOpen
                                        ? 'border-indigo-300 bg-indigo-50 text-indigo-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Start-of-day Balance sheet
                            </Link>
                            <Link
                                href={reportLinks?.cash_flow ?? '#'}
                                className={`rounded-md border px-3 py-2 text-sm ${
                                    !dayOpen
                                        ? 'border-indigo-300 bg-indigo-50 text-indigo-900'
                                        : 'pointer-events-none border-slate-200 bg-slate-100 text-slate-400'
                                }`}
                            >
                                Start-of-day Cash flow
                            </Link>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <div className="border-b border-gray-200 px-4 py-3">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Your recent closes
                            </h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Date</th>
                                    <th className="px-3 py-2 text-left">Status</th>
                                    <th className="px-3 py-2 text-right">Opening</th>
                                    <th className="px-3 py-2 text-right">Counted</th>
                                    <th className="px-3 py-2 text-right">Δ vs opening</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {recentCloses.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No closes yet.
                                        </td>
                                    </tr>
                                ) : (
                                    recentCloses.map((c) => (
                                        <tr key={c.id}>
                                            <td className="px-3 py-2">{c.close_date}</td>
                                            <td className="px-3 py-2">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs ${
                                                        c.day_status === 'open'
                                                            ? 'bg-amber-100 text-amber-800'
                                                            : 'bg-emerald-100 text-emerald-800'
                                                    }`}
                                                >
                                                    {c.day_status}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(c.opening_cash_cents)}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(c.counted_cash_cents)}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(c.variance_versus_opening_cents)}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
