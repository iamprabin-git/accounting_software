import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export default function DayClose({ recentCloses, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        close_date: new Date().toISOString().slice(0, 10),
        opening_cash: '',
        counted_cash: '',
        expected_cash: '',
        memo: '',
        company_id: isAdmin ? currentCompanyId : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            form.setData('company_id', currentCompanyId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sync company picker only
    }, [currentCompanyId, isAdmin]);

    const submit = (e) => {
        e.preventDefault();
        form.post(route('teller.day-close.store', query));
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
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        {isAdmin && (
                            <input type="hidden" name="company_id" value={form.data.company_id} />
                        )}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Close date
                                </label>
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.close_date}
                                    onChange={(e) =>
                                        form.setData('close_date', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={form.errors.close_date} className="mt-1" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Opening cash (float)
                                </label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.opening_cash}
                                    onChange={(e) =>
                                        form.setData('opening_cash', e.target.value)
                                    }
                                    placeholder="0.00"
                                    required
                                />
                                <InputError message={form.errors.opening_cash} className="mt-1" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Counted cash
                                </label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.counted_cash}
                                    onChange={(e) =>
                                        form.setData('counted_cash', e.target.value)
                                    }
                                    placeholder="0.00"
                                    required
                                />
                                <InputError message={form.errors.counted_cash} className="mt-1" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Expected cash (optional)
                                </label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.expected_cash}
                                    onChange={(e) =>
                                        form.setData('expected_cash', e.target.value)
                                    }
                                    placeholder="From system / worksheet"
                                />
                                <InputError message={form.errors.expected_cash} className="mt-1" />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Memo
                            </label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                value={form.data.memo}
                                onChange={(e) => form.setData('memo', e.target.value)}
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Save day close
                        </button>
                    </form>

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
                                    <th className="px-3 py-2 text-right">Opening</th>
                                    <th className="px-3 py-2 text-right">Counted</th>
                                    <th className="px-3 py-2 text-right">Δ vs opening</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {recentCloses.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No closes yet.
                                        </td>
                                    </tr>
                                ) : (
                                    recentCloses.map((c) => (
                                        <tr key={c.id}>
                                            <td className="px-3 py-2">{c.close_date}</td>
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
