import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Create({
    bankAccounts,
    companies,
    currentCompanyId,
    csvHint,
    bankFeedProviders,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        chart_account_id: bankAccounts?.[0]?.id ?? '',
        name: '',
        statement_opening_balance: '',
        statement_closing_balance: '',
        csv: '',
        csv_file: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('bank-reconciliation.store', q), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Import bank statement
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {isAdmin ? (
                            <CompanyPicker
                                companies={companies}
                                currentCompanyId={currentCompanyId}
                                routeName="bank-reconciliation.create"
                                routeParams={{}}
                                query={{}}
                            />
                        ) : null}
                        <Link
                            href={route('bank-reconciliation.index', q)}
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Back to list
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Import bank statement" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 rounded border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <p className="text-sm text-gray-600">{csvHint}</p>

                        {!bankAccounts?.length ? (
                            <div className="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                                No approved asset accounts yet. Add and approve a
                                bank or cash account in the chart of accounts first.
                            </div>
                        ) : null}

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Bank / cash account (asset)
                            </label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                value={form.data.chart_account_id}
                                onChange={(e) =>
                                    form.setData(
                                        'chart_account_id',
                                        e.target.value,
                                    )
                                }
                                required
                                disabled={!bankAccounts?.length}
                            >
                                {(bankAccounts ?? []).map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.chart_account_id ? (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.chart_account_id}
                                </p>
                            ) : null}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Statement opening balance (optional)
                                </label>
                                <input
                                    type="text"
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                    value={
                                        form.data.statement_opening_balance
                                    }
                                    onChange={(e) =>
                                        form.setData(
                                            'statement_opening_balance',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. 10000.00"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    If you also enter closing, we check opening
                                    + sum(lines) = closing.
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Statement closing balance (optional)
                                </label>
                                <input
                                    type="text"
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                    value={
                                        form.data.statement_closing_balance
                                    }
                                    onChange={(e) =>
                                        form.setData(
                                            'statement_closing_balance',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. 10250.50"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Batch name (optional)
                            </label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="e.g. March 2026 statement"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                CSV file
                            </label>
                            <input
                                type="file"
                                accept=".csv,.txt"
                                className="mt-1 block w-full text-sm"
                                onChange={(e) =>
                                    form.setData(
                                        'csv_file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">
                                Or paste CSV
                            </label>
                            <textarea
                                rows={8}
                                className="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs"
                                value={form.data.csv}
                                onChange={(e) =>
                                    form.setData('csv', e.target.value)
                                }
                                placeholder={`date,amount,description,reference\n2026-04-01,150.00,Deposit,REF1`}
                            />
                            {form.errors.csv ? (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.csv}
                                </p>
                            ) : null}
                        </div>

                        {bankFeedProviders?.length ? (
                            <p className="text-xs text-gray-500">
                                <span className="font-medium text-gray-700">
                                    API feeds:
                                </span>{' '}
                                Configure Plaid / TrueLayer in{' '}
                                <code className="rounded bg-gray-100 px-1">
                                    .env
                                </code>
                                ; CSV import works today. On the batch screen you
                                can check feed status.
                            </p>
                        ) : null}

                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={form.processing || !bankAccounts?.length}
                                className="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                            >
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
