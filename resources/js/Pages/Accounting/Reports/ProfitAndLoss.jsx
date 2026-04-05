import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ProfitAndLoss({
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
            route('reports.profit-loss'),
            {
                from: fromD,
                to: toD,
                company_id: isAdmin ? currentCompanyId : undefined,
            },
            { preserveState: true },
        );
    };

    const printHref = route('reports.profit-loss', {
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
                        Profit &amp; loss
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="reports.profit-loss"
                            routeParams={{}}
                            query={{ from: fromD, to: toD }}
                        />
                        <Link
                            href={printHref}
                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm hover:bg-gray-50"
                        >
                            Print
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Profit & loss" />

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
                        <button
                            type="submit"
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        >
                            Apply
                        </button>
                    </form>

                    <div className="mb-4 hidden print:block">
                        <h1 className="text-2xl font-bold">
                            Profit &amp; loss
                        </h1>
                        <p className="text-sm text-gray-700">
                            {from} — {to}
                        </p>
                    </div>

                    <div className="space-y-6 rounded bg-white p-6 shadow print:shadow-none">
                        <section>
                            <h3 className="font-semibold text-gray-900">
                                Revenue
                            </h3>
                            <ul className="mt-2 divide-y text-sm">
                                {report.revenue.length === 0 ? (
                                    <li className="py-2 text-gray-500">
                                        No revenue in period.
                                    </li>
                                ) : (
                                    report.revenue.map((r) => (
                                        <li
                                            key={r.code}
                                            className="flex justify-between py-2"
                                        >
                                            <span>
                                                {r.code} {r.name}
                                            </span>
                                            <span className="tabular-nums">
                                                {moneyFromCents(r.amount_cents)}
                                            </span>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </section>

                        <section>
                            <h3 className="font-semibold text-gray-900">
                                Expenses
                            </h3>
                            <ul className="mt-2 divide-y text-sm">
                                {report.expenses.length === 0 ? (
                                    <li className="py-2 text-gray-500">
                                        No expenses in period.
                                    </li>
                                ) : (
                                    report.expenses.map((r) => (
                                        <li
                                            key={r.code}
                                            className="flex justify-between py-2"
                                        >
                                            <span>
                                                {r.code} {r.name}
                                            </span>
                                            <span className="tabular-nums">
                                                {moneyFromCents(r.amount_cents)}
                                            </span>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </section>

                        <div className="flex justify-between border-t-2 border-gray-900 pt-4 text-base font-semibold">
                            <span>Net income</span>
                            <span className="tabular-nums">
                                {moneyFromCents(report.net_income_cents)}
                            </span>
                        </div>
                    </div>

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
