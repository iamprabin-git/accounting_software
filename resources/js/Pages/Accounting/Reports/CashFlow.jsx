import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CashFlow({
    report,
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

    usePrintWhenReady(Boolean(printMode));

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('reports.cash-flow'),
            {
                from: fromD,
                to: toD,
                company_id: isAdmin ? currentCompanyId : undefined,
            },
            { preserveState: true },
        );
    };

    const printHref = route('reports.cash-flow', {
        from: fromD,
        to: toD,
        print: 1,
        company_id: isAdmin ? currentCompanyId : undefined,
    });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Cash flow (simplified)
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="reports.cash-flow"
                            routeParams={{}}
                            query={{ from: fromD, to: toD }}
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
            <Head title="Cash flow" />

            <div className="py-8 print:py-4">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <form
                        onSubmit={apply}
                        className="mb-6 flex flex-wrap items-end gap-3 print:hidden"
                    >
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
                        <h1 className="text-2xl font-bold">
                            Cash flow (simplified)
                        </h1>
                        <p className="text-sm text-gray-700">
                            {from} — {to}
                        </p>
                    </div>

                    <Card className="print:shadow-none">
                        <CardContent className="space-y-6 p-6">
                        <p className="text-sm text-gray-600">
                            This summary uses the same approved journal activity
                            as the general ledger. Net income matches the P&amp;L
                            for the period. Cash / bank lines include accounts
                            whose names contain cash, bank, checking, or
                            savings.
                        </p>

                        <div className="flex justify-between border-b pb-3 text-sm font-semibold">
                            <span>Net income (period)</span>
                            <span className="tabular-nums">
                                {moneyFromCents(report.net_income_cents)}
                            </span>
                        </div>

                        <section>
                            <h3 className="font-semibold text-gray-900">
                                Cash and bank accounts
                            </h3>
                            <div className="overflow-x-auto">
                            <table className="mt-2 w-full min-w-[640px] text-sm">
                                <thead>
                                    <tr className="text-left text-gray-600">
                                        <th className="py-2">Account</th>
                                        <th className="py-2 text-right">
                                            Opening
                                        </th>
                                        <th className="py-2 text-right">
                                            Closing
                                        </th>
                                        <th className="py-2 text-right">
                                            Change
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {report.cash_accounts.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="py-3 text-gray-500"
                                            >
                                                No matching cash/bank accounts or
                                                no movement.
                                            </td>
                                        </tr>
                                    ) : (
                                        report.cash_accounts.map((r) => (
                                            <tr key={r.code}>
                                                <td className="py-2">
                                                    {r.code} {r.name}
                                                </td>
                                                <td className="py-2 text-right tabular-nums">
                                                    {moneyFromCents(
                                                        r.opening_cents,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right tabular-nums">
                                                    {moneyFromCents(
                                                        r.closing_cents,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right tabular-nums font-medium">
                                                    {moneyFromCents(
                                                        r.change_cents,
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                            </div>
                        </section>
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
