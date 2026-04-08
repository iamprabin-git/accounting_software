import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function TrialBalance({
    report,
    as_of,
    show_zero = true,
    printMode,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const [asOf, setAsOf] = useState(as_of);
    const [showZero, setShowZero] = useState(Boolean(show_zero));

    usePrintWhenReady(Boolean(printMode));

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('reports.trial-balance'),
            {
                as_of: asOf,
                show_zero: showZero ? 1 : 0,
                company_id: isAdmin ? currentCompanyId : undefined,
            },
            { preserveState: true },
        );
    };

    const printHref = route('reports.trial-balance', {
        as_of: asOf,
        show_zero: showZero ? 1 : 0,
        print: 1,
        company_id: isAdmin ? currentCompanyId : undefined,
    });

    const reportQuery = isAdmin ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Trial balance
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="reports.trial-balance"
                            routeParams={{}}
                            query={{
                                as_of: asOf,
                                show_zero: showZero ? 1 : 0,
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
            <Head title="Trial balance" />

            <div className="py-8 print:py-4">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <form onSubmit={apply} className="mb-6 flex flex-wrap items-end gap-3 print:hidden">
                        <div>
                            <label className="block text-xs font-medium text-gray-600">
                                As of
                            </label>
                            <input
                                type="date"
                                value={asOf}
                                onChange={(e) => setAsOf(e.target.value)}
                                className="mt-1 rounded-md border-gray-300"
                            />
                        </div>
                        <label className="mb-2 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                checked={showZero}
                                onChange={(e) => setShowZero(e.target.checked)}
                                className="rounded border-gray-300"
                            />
                            Show zero balances
                        </label>
                        <Button type="submit" size="sm">
                            Apply
                        </Button>
                    </form>

                    <div className="mb-4 hidden print:block">
                        <h1 className="text-2xl font-bold">Trial balance</h1>
                        <p className="text-sm text-gray-700">As of {as_of}</p>
                    </div>

                    <Card className="overflow-hidden print:shadow-none">
                        <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y text-sm">
                            <thead className="bg-gray-50 print:bg-white">
                                <tr>
                                    <th className="px-4 py-2 text-left">Code</th>
                                    <th className="px-4 py-2 text-left">Account</th>
                                    <th className="px-4 py-2 text-left">Type</th>
                                    <th className="px-4 py-2 text-right">Debit</th>
                                    <th className="px-4 py-2 text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {report.accounts.map((row, i) => (
                                    <tr
                                        key={i}
                                        className={
                                            row.inventory_extension ||
                                            row.finance_rollup
                                                ? 'bg-slate-50 text-slate-800'
                                                : ''
                                        }
                                    >
                                        <td className="px-4 py-2 font-mono text-xs">
                                            {row.code}
                                        </td>
                                        <td className="px-4 py-2">{row.name}</td>
                                        <td className="px-4 py-2 text-gray-600">
                                            {row.type}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {moneyFromCents(row.debit_cents)}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {moneyFromCents(row.credit_cents)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="border-t-2 border-gray-900 bg-gray-50 font-medium print:bg-white">
                                <tr>
                                    <td
                                        colSpan={3}
                                        className="px-4 py-2 text-right"
                                    >
                                        Totals
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {moneyFromCents(
                                            report.totals.debit_cents,
                                        )}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {moneyFromCents(
                                            report.totals.credit_cents,
                                        )}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                        </CardContent>
                    </Card>

                    {report.inventory_at_cost_cents > 0 && (
                        <p className="mt-3 text-sm text-gray-600 print:text-gray-800">
                            Inventory at cost (Σ qty × unit cost):{' '}
                            <span className="font-medium tabular-nums">
                                {moneyFromCents(
                                    report.inventory_at_cost_cents,
                                )}
                            </span>
                            . Shaded rows extend the trial balance with a
                            matching offset so debits still equal credits; post
                            real journals when you capitalize inventory.
                        </p>
                    )}

                    <p className="mt-3 text-sm text-gray-600 print:text-gray-800">
                        Member loan and savings sub-ledgers are rolled into the
                        shaded summary lines (Σ-LOANS / Σ-SAVINGS). Per-member
                        balances and personal details are on the{' '}
                        <Link
                            href={route(
                                'reports.loan-accounts',
                                reportQuery,
                            )}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            Statement of loans
                        </Link>{' '}
                        and{' '}
                        <Link
                            href={route(
                                'reports.savings-accounts',
                                reportQuery,
                            )}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            Statement of savings
                        </Link>
                        .
                    </p>

                    <p className="mt-4 text-sm text-gray-500 print:hidden">
                        <Link
                            href={route('reports.index', {
                                company_id: isAdmin
                                    ? currentCompanyId
                                    : undefined,
                            })}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            ← All reports
                        </Link>
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
