import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import WorkDatePicker from '@/Components/WorkDatePicker';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

const emptyLine = () => ({
    chart_account_id: '',
    amount: '',
    description: '',
});

export default function CashEntryCreate({
    mode,
    accounts,
    defaultCashAccountId = null,
    defaultCashAccountLabel = null,
    companies,
    currentCompanyId,
}) {
    const isIn = mode === 'in';
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        cash_chart_account_id:
            defaultCashAccountId != null ? String(defaultCashAccountId) : '',
        reference: '',
        memo: '',
        transaction_date: new Date().toISOString().slice(0, 10),
        company_id: isAdmin ? currentCompanyId : '',
        lines: [emptyLine()],
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    useEffect(() => {
        if (defaultCashAccountId != null) {
            setData('cash_chart_account_id', String(defaultCashAccountId));
        }
    }, [defaultCashAccountId, setData]);

    const counterpartAccounts = useMemo(() => {
        const id = data.cash_chart_account_id;
        if (!id) return accounts;
        return accounts.filter((a) => String(a.id) !== String(id));
    }, [accounts, data.cash_chart_account_id]);

    const totalDollars = useMemo(() => {
        return data.lines.reduce((sum, line) => {
            const v = parseFloat(line.amount);
            return sum + (Number.isFinite(v) && v > 0 ? v : 0);
        }, 0);
    }, [data.lines]);

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (idx) => {
        if (data.lines.length <= 1) return;
        setData(
            'lines',
            data.lines.filter((_, i) => i !== idx),
        );
    };

    const updateLine = (idx, field, value) => {
        const next = data.lines.map((line, i) =>
            i === idx ? { ...line, [field]: value } : line,
        );
        setData('lines', next);
    };

    const submit = (e) => {
        e.preventDefault();
        const routeName = isIn ? 'journals.store-cash-in' : 'journals.store-cash-out';
        post(route(routeName));
    };

    const title = isIn ? 'Cash in entry' : 'Cash out entry';
    const routeName = isIn ? 'journals.create-cash-in' : 'journals.create-cash-out';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {title}
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName={routeName}
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={title} />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg border border-indigo-100 bg-indigo-50/90 px-4 py-3 text-sm text-indigo-950">
                        {isIn ? (
                            <>
                                <strong>Cash in (receipt):</strong> money received.
                                The <strong>cash in hand</strong> account is debited
                                for the total. Each line below is a{' '}
                                <strong>credit</strong> to the account you choose
                                (e.g. sales, loan recovery, capital).
                            </>
                        ) : (
                            <>
                                <strong>Cash out (payment):</strong> money paid.
                                Each line below is a <strong>debit</strong> to the
                                account you choose (e.g. expense, supplier). The{' '}
                                <strong>cash in hand</strong> account is credited
                                for the total.
                            </>
                        )}
                    </div>

                    <Card>
                        <CardContent className="p-6 sm:p-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id}
                            />
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <WorkDatePicker
                                    id="transaction_date"
                                    label="Transaction date"
                                    value={data.transaction_date}
                                    onChange={(iso) =>
                                        setData('transaction_date', iso)
                                    }
                                    error={errors.transaction_date}
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel value="Reference" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.reference}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="cash_chart_account_id"
                                value="Cash in hand (or bank) account"
                            />
                            {defaultCashAccountId != null ? (
                                <>
                                    <input
                                        type="hidden"
                                        name="cash_chart_account_id"
                                        value={data.cash_chart_account_id}
                                    />
                                    <TextInput
                                        id="cash_chart_account_id"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={
                                            defaultCashAccountLabel ||
                                            'Cash in Hand'
                                        }
                                        readOnly
                                    />
                                </>
                            ) : (
                                <select
                                    id="cash_chart_account_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.cash_chart_account_id}
                                    onChange={(e) =>
                                        setData(
                                            'cash_chart_account_id',
                                            e.target.value,
                                        )
                                    }
                                    required
                                >
                                    <option value="">
                                        Select cash / bank account...
                                    </option>
                                    {accounts.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                            <InputError
                                message={errors.cash_chart_account_id}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel value="Memo (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.memo}
                                onChange={(e) => setData('memo', e.target.value)}
                                placeholder={
                                    isIn
                                        ? 'e.g. Daily collections'
                                        : 'e.g. Office supplies paid in cash'
                                }
                            />
                            <InputError message={errors.memo} className="mt-2" />
                        </div>

                        <div>
                            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <InputLabel
                                        value={
                                            isIn
                                                ? 'Credit accounts (sources / reasons)'
                                                : 'Debit accounts (what was paid for)'
                                        }
                                    />
                                    <p className="mt-0.5 text-xs text-gray-500">
                                        Enter amounts only — debits and credits are
                                        applied automatically.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    className="text-sm text-indigo-600 hover:text-indigo-800"
                                    onClick={addLine}
                                >
                                    + Add line
                                </button>
                            </div>
                            <InputError message={errors.lines} className="mb-2" />

                            <div className="overflow-x-auto rounded border border-gray-200">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-2 py-2 text-left font-medium text-gray-600">
                                                Account
                                            </th>
                                            <th className="px-2 py-2 text-right font-medium text-gray-600">
                                                Amount
                                            </th>
                                            <th className="px-2 py-2 text-left font-medium text-gray-600">
                                                Description
                                            </th>
                                            <th className="px-2 py-2 w-10" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {data.lines.map((line, idx) => (
                                            <tr key={idx}>
                                                <td className="px-2 py-2">
                                                    <select
                                                        className="w-full rounded border-gray-300 text-sm"
                                                        value={line.chart_account_id}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'chart_account_id',
                                                                e.target.value,
                                                            )
                                                        }
                                                        required
                                                    >
                                                        <option value="">
                                                            Select account
                                                        </option>
                                                        {counterpartAccounts.map(
                                                            (a) => (
                                                                <option
                                                                    key={a.id}
                                                                    value={a.id}
                                                                >
                                                                    {a.label}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `lines.${idx}.chart_account_id`
                                                            ]
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <TextInput
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        className="w-full text-right"
                                                        value={line.amount}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'amount',
                                                                e.target.value,
                                                            )
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `lines.${idx}.amount`
                                                            ]
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <TextInput
                                                        className="w-full"
                                                        value={line.description}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'description',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2 text-right">
                                                    <button
                                                        type="button"
                                                        className="text-red-600 hover:text-red-800"
                                                        onClick={() =>
                                                            removeLine(idx)
                                                        }
                                                    >
                                                        ✕
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm">
                                <span className="font-medium text-slate-700">
                                    {isIn ? (
                                        <>
                                            Cash (debit){' '}
                                            <span className="tabular-nums text-slate-900">
                                                {totalDollars.toFixed(2)}
                                            </span>
                                        </>
                                    ) : (
                                        <>
                                            Cash (credit){' '}
                                            <span className="tabular-nums text-slate-900">
                                                {totalDollars.toFixed(2)}
                                            </span>
                                        </>
                                    )}
                                </span>
                                <span className="text-slate-600">
                                    {isIn
                                        ? 'Sum of credits must match.'
                                        : 'Sum of debits must match.'}
                                </span>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Save draft
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={route('journals.index', {
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                            >
                                Cancel
                            </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                            <Link
                                href={route('journals.create', {
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                            >
                                Full journal (manual debits &amp; credits)
                            </Link>
                            </Button>
                        </div>
                    </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
