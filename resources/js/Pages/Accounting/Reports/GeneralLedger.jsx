import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function GeneralLedger({
    report,
    accounts,
    account_id,
    from,
    to,
    printMode,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const [fromD, setFromD] = useState(from);
    const [toD, setToD] = useState(to);
    const [accountId, setAccountId] = useState(String(account_id));

    usePrintWhenReady(Boolean(printMode));

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('reports.general-ledger'),
            {
                from: fromD,
                to: toD,
                account_id: accountId,
                company_id: isAdmin ? currentCompanyId : undefined,
            },
            { preserveState: true },
        );
    };

    const printHref = route('reports.general-ledger', {
        from: fromD,
        to: toD,
        account_id: accountId,
        print: 1,
        company_id: isAdmin ? currentCompanyId : undefined,
    });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        General ledger
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="reports.general-ledger"
                            routeParams={{}}
                            query={{
                                from: fromD,
                                to: toD,
                                account_id: accountId,
                            }}
                        />
                        <Link href={printHref}>
                            <Button variant="outline" size="sm">
                            Print
                            </Button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="General ledger" />

            <div className="py-8 print:py-4">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <form
                        onSubmit={apply}
                        className="mb-6 flex flex-wrap items-end gap-3 print:hidden"
                    >
                        <div>
                            <label className="block text-xs font-medium text-gray-600">
                                Account
                            </label>
                            <select
                                value={accountId}
                                onChange={(e) => setAccountId(e.target.value)}
                                className="mt-1 min-w-[220px] rounded-md border-gray-300"
                            >
                                {accounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600">
                                From
                            </label>
                            <input
                                type="date"
                                value={fromD}
                                onChange={(e) => setFromD(e.target.value)}
                                className="mt-1 rounded-md border-gray-300"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-600">
                                To
                            </label>
                            <input
                                type="date"
                                value={toD}
                                onChange={(e) => setToD(e.target.value)}
                                className="mt-1 rounded-md border-gray-300"
                            />
                        </div>
                        <Button type="submit" size="sm">
                            Apply
                        </Button>
                    </form>

                    <div className="mb-4 hidden print:block">
                        <h1 className="text-2xl font-bold">General ledger</h1>
                        {report.account && (
                            <p className="text-sm text-gray-700">
                                {report.account.code} — {report.account.name}{' '}
                                · {from} — {to}
                            </p>
                        )}
                    </div>

                    {report.account && (
                        <p className="mb-4 text-sm text-gray-600 print:hidden">
                            Opening balance:{' '}
                            <span className="font-medium tabular-nums">
                                {moneyFromCents(
                                    report.opening_balance_cents,
                                )}
                            </span>
                        </p>
                    )}

                    <Card className="overflow-hidden print:shadow-none">
                        <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y text-sm">
                            <thead className="bg-gray-50 print:bg-white">
                                <tr>
                                    <th className="px-3 py-2 text-left">Date</th>
                                    <th className="px-3 py-2 text-left">Ref</th>
                                    <th className="px-3 py-2 text-left">Memo</th>
                                    <th className="px-3 py-2 text-left">Detail</th>
                                    <th className="px-3 py-2 text-right">
                                        Debit
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        Credit
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        Balance
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {report.lines.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No posted lines in this range
                                            (approved entries only).
                                        </td>
                                    </tr>
                                ) : (
                                    report.lines.map((line) => (
                                        <tr key={line.id}>
                                            <td className="whitespace-nowrap px-3 py-2">
                                                {line.date}
                                            </td>
                                            <td className="px-3 py-2">
                                                {line.reference || '—'}
                                            </td>
                                            <td className="max-w-[140px] truncate px-3 py-2">
                                                {line.memo || '—'}
                                            </td>
                                            <td className="max-w-[160px] truncate px-3 py-2">
                                                {line.description || '—'}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {line.debit_cents > 0
                                                    ? moneyFromCents(
                                                          line.debit_cents,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {line.credit_cents > 0
                                                    ? moneyFromCents(
                                                          line.credit_cents,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums font-medium">
                                                {moneyFromCents(
                                                    line.balance_cents,
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                        </CardContent>
                    </Card>

                    <p className="mt-4 text-sm print:hidden">
                        <Link
                            href={route('reports.index', {
                                company_id: isAdmin
                                    ? currentCompanyId
                                    : undefined,
                            })}
                            className="text-indigo-600"
                        >
                            ← All reports
                        </Link>
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
