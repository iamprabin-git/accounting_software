import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export default function Show({
    group,
    members,
    debitAccounts,
    interestRevenueAccounts,
    batches,
    loanBatches,
    companies,
    currentCompanyId,
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        transaction_date: new Date().toISOString().slice(0, 10),
        debit_chart_account_id: debitAccounts[0]?.id ?? '',
        reference: '',
        memo: '',
        lines: members
            .filter((m) => m.can_deposit)
            .map((m) => ({
                member_id: m.member_id,
                financial_position_id: m.savings_position_id,
                amount: '',
            })),
    });

    const loanForm = useForm({
        transaction_date: new Date().toISOString().slice(0, 10),
        debit_chart_account_id: debitAccounts[0]?.id ?? '',
        interest_revenue_chart_account_id: interestRevenueAccounts[0]?.id ?? '',
        penalty_credit_chart_account_id: debitAccounts[0]?.id ?? '',
        reference: '',
        memo: '',
        lines: members
            .filter((m) => m.can_collect_loan)
            .map((m) => ({
                member_id: m.member_id,
                financial_position_id: m.loan_position_id,
                principal: '',
                interest: '',
                penalty: '',
            })),
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(
            route('member-groups.deposit-batches.store', {
                group: group.id,
                ...query,
            }),
        );
    };

    const submitLoan = (e) => {
        e.preventDefault();
        loanForm.post(
            route('member-groups.loan-collection-batches.store', {
                group: group.id,
                ...query,
            }),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            Group · {group.name}
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            {group.code || '—'} · Meeting {group.meeting_day || '—'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="member-groups.show"
                            routeParams={{ group: group.id }}
                            query={{}}
                        />
                        <Link
                            href={route('member-groups.index', query)}
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Back to groups
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Group ${group.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-5 shadow"
                    >
                        <p className="text-sm text-gray-600">
                            Group deposit batch posts one approved savings journal per
                            member line and updates each member savings balance.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-4">
                            <input
                                type="date"
                                className="rounded-md border-gray-300 text-sm"
                                value={form.data.transaction_date}
                                onChange={(e) =>
                                    form.setData('transaction_date', e.target.value)
                                }
                                required
                            />
                            <select
                                className="rounded-md border-gray-300 text-sm"
                                value={form.data.debit_chart_account_id}
                                onChange={(e) =>
                                    form.setData('debit_chart_account_id', e.target.value)
                                }
                                required
                            >
                                {debitAccounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Reference"
                                value={form.data.reference}
                                onChange={(e) => form.setData('reference', e.target.value)}
                            />
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Memo"
                                value={form.data.memo}
                                onChange={(e) => form.setData('memo', e.target.value)}
                            />
                        </div>

                        <div className="overflow-x-auto rounded border">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left">Member</th>
                                        <th className="px-3 py-2 text-left">Savings a/c</th>
                                        <th className="px-3 py-2 text-right">Current balance</th>
                                        <th className="px-3 py-2 text-right">Deposit amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {members.map((m) => {
                                        const idx = form.data.lines.findIndex(
                                            (row) => row.member_id === m.member_id,
                                        );
                                        const row = idx >= 0 ? form.data.lines[idx] : null;

                                        return (
                                            <tr key={m.member_id}>
                                                <td className="px-3 py-2">
                                                    #{m.member_number} {m.member_name}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {m.savings_account_number || (
                                                        <span className="text-red-700">
                                                            No operational savings
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right font-mono">
                                                    {m.savings_principal_cents == null
                                                        ? '—'
                                                        : money(m.savings_principal_cents)}
                                                </td>
                                                <td className="px-3 py-2 text-right">
                                                    {row ? (
                                                        <input
                                                            className="w-28 rounded-md border-gray-300 text-right text-sm"
                                                            placeholder="0.00"
                                                            value={row.amount}
                                                            onChange={(e) => {
                                                                const next = [...form.data.lines];
                                                                next[idx] = {
                                                                    ...next[idx],
                                                                    amount: e.target.value,
                                                                };
                                                                form.setData('lines', next);
                                                            }}
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-gray-400">
                                                            blocked
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Post group deposit batch
                        </button>
                    </form>

                    <form
                        onSubmit={submitLoan}
                        className="space-y-4 rounded-lg bg-white p-5 shadow"
                    >
                        <h3 className="text-base font-semibold text-gray-900">
                            Group loan collection
                        </h3>
                        <p className="text-sm text-gray-600">
                            Posts separate journals per line for principal
                            (installment), interest (cash vs interest income), and
                            penalty (same pattern as single loan penalty). Penalty
                            is applied before principal on each row.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <input
                                type="date"
                                className="rounded-md border-gray-300 text-sm"
                                value={loanForm.data.transaction_date}
                                onChange={(e) =>
                                    loanForm.setData('transaction_date', e.target.value)
                                }
                                required
                            />
                            <select
                                className="rounded-md border-gray-300 text-sm"
                                value={loanForm.data.debit_chart_account_id}
                                onChange={(e) =>
                                    loanForm.setData('debit_chart_account_id', e.target.value)
                                }
                                required
                            >
                                {debitAccounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        Cash: {a.label}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="rounded-md border-gray-300 text-sm"
                                value={loanForm.data.interest_revenue_chart_account_id}
                                onChange={(e) =>
                                    loanForm.setData(
                                        'interest_revenue_chart_account_id',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">Interest income (if needed)</option>
                                {interestRevenueAccounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="rounded-md border-gray-300 text-sm"
                                value={loanForm.data.penalty_credit_chart_account_id}
                                onChange={(e) =>
                                    loanForm.setData(
                                        'penalty_credit_chart_account_id',
                                        e.target.value,
                                    )
                                }
                            >
                                {debitAccounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        Penalty credit: {a.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Reference"
                                value={loanForm.data.reference}
                                onChange={(e) =>
                                    loanForm.setData('reference', e.target.value)
                                }
                            />
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Memo"
                                value={loanForm.data.memo}
                                onChange={(e) => loanForm.setData('memo', e.target.value)}
                            />
                        </div>
                        {loanForm.errors.loan_lines && (
                            <p className="text-sm text-red-600">
                                {loanForm.errors.loan_lines}
                            </p>
                        )}
                        {loanForm.errors.interest_revenue_chart_account_id && (
                            <p className="text-sm text-red-600">
                                {loanForm.errors.interest_revenue_chart_account_id}
                            </p>
                        )}
                        {loanForm.errors.penalty_credit_chart_account_id && (
                            <p className="text-sm text-red-600">
                                {loanForm.errors.penalty_credit_chart_account_id}
                            </p>
                        )}

                        <div className="overflow-x-auto rounded border">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left">Member</th>
                                        <th className="px-3 py-2 text-left">Loan a/c</th>
                                        <th className="px-3 py-2 text-right">Balance</th>
                                        <th className="px-3 py-2 text-right">Principal</th>
                                        <th className="px-3 py-2 text-right">Interest</th>
                                        <th className="px-3 py-2 text-right">Penalty</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {members.map((m) => {
                                        const idx = loanForm.data.lines.findIndex(
                                            (row) => row.member_id === m.member_id,
                                        );
                                        const row = idx >= 0 ? loanForm.data.lines[idx] : null;

                                        return (
                                            <tr key={m.member_id}>
                                                <td className="px-3 py-2">
                                                    #{m.member_number} {m.member_name}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {m.loan_account_number || (
                                                        <span className="text-gray-500">
                                                            No operational loan
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right font-mono">
                                                    {m.loan_principal_cents == null
                                                        ? '—'
                                                        : money(m.loan_principal_cents)}
                                                </td>
                                                <td className="px-3 py-2 text-right">
                                                    {row ? (
                                                        <input
                                                            className="w-24 rounded-md border-gray-300 text-right text-sm"
                                                            placeholder="0"
                                                            value={row.principal}
                                                            onChange={(e) => {
                                                                const next = [
                                                                    ...loanForm.data.lines,
                                                                ];
                                                                next[idx] = {
                                                                    ...next[idx],
                                                                    principal: e.target.value,
                                                                };
                                                                loanForm.setData('lines', next);
                                                            }}
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-gray-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right">
                                                    {row ? (
                                                        <input
                                                            className="w-24 rounded-md border-gray-300 text-right text-sm"
                                                            placeholder="0"
                                                            value={row.interest}
                                                            onChange={(e) => {
                                                                const next = [
                                                                    ...loanForm.data.lines,
                                                                ];
                                                                next[idx] = {
                                                                    ...next[idx],
                                                                    interest: e.target.value,
                                                                };
                                                                loanForm.setData('lines', next);
                                                            }}
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-gray-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right">
                                                    {row ? (
                                                        <input
                                                            className="w-24 rounded-md border-gray-300 text-right text-sm"
                                                            placeholder="0"
                                                            value={row.penalty}
                                                            onChange={(e) => {
                                                                const next = [
                                                                    ...loanForm.data.lines,
                                                                ];
                                                                next[idx] = {
                                                                    ...next[idx],
                                                                    penalty: e.target.value,
                                                                };
                                                                loanForm.setData('lines', next);
                                                            }}
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-gray-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <button
                            type="submit"
                            disabled={loanForm.processing}
                            className="rounded-md bg-indigo-700 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 disabled:opacity-50"
                        >
                            Post group loan collection batch
                        </button>
                    </form>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <div className="border-b border-gray-100 px-4 py-2 text-sm font-medium text-gray-700">
                            Savings deposit batches
                        </div>
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Date</th>
                                    <th className="px-3 py-2 text-left">Reference</th>
                                    <th className="px-3 py-2 text-left">Debit account</th>
                                    <th className="px-3 py-2 text-right">Lines</th>
                                    <th className="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {batches.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No batches posted yet.
                                        </td>
                                    </tr>
                                ) : (
                                    batches.map((b) => (
                                        <tr key={b.id}>
                                            <td className="px-3 py-2">{b.transaction_date}</td>
                                            <td className="px-3 py-2">{b.reference || '—'}</td>
                                            <td className="px-3 py-2">{b.debit_account || '—'}</td>
                                            <td className="px-3 py-2 text-right">{b.line_count}</td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(b.total_cents)}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <div className="border-b border-gray-100 px-4 py-2 text-sm font-medium text-gray-700">
                            Loan collection batches
                        </div>
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Date</th>
                                    <th className="px-3 py-2 text-left">Ref</th>
                                    <th className="px-3 py-2 text-left">Cash a/c</th>
                                    <th className="px-3 py-2 text-right">Lines</th>
                                    <th className="px-3 py-2 text-right">Principal</th>
                                    <th className="px-3 py-2 text-right">Interest</th>
                                    <th className="px-3 py-2 text-right">Penalty</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loanBatches.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No loan batches yet.
                                        </td>
                                    </tr>
                                ) : (
                                    loanBatches.map((b) => (
                                        <tr key={b.id}>
                                            <td className="px-3 py-2">{b.transaction_date}</td>
                                            <td className="px-3 py-2">{b.reference || '—'}</td>
                                            <td className="px-3 py-2">{b.cash_account || '—'}</td>
                                            <td className="px-3 py-2 text-right">{b.line_count}</td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(b.total_principal_cents)}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(b.total_interest_cents)}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(b.total_penalty_cents)}
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

