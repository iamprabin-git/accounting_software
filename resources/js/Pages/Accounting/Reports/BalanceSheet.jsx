import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function Section({ title, rows }) {
    return (
        <section className="mb-6">
            <h3 className="border-b pb-2 font-semibold text-gray-900">
                {title}
            </h3>
            <ul className="mt-2 divide-y text-sm">
                {rows.length === 0 ? (
                    <li className="py-2 text-gray-500">—</li>
                ) : (
                    rows.map((r) => (
                        <li
                            key={r.code}
                            className="flex justify-between py-2"
                        >
                            <span>
                                {r.code} {r.name}
                            </span>
                            <span className="tabular-nums">
                                {moneyFromCents(r.balance_cents)}
                            </span>
                        </li>
                    ))
                )}
            </ul>
        </section>
    );
}

export default function BalanceSheet({
    report,
    as_of,
    printMode,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const [asOf, setAsOf] = useState(as_of);

    usePrintWhenReady(Boolean(printMode));

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('reports.balance-sheet'),
            {
                as_of: asOf,
                company_id: isAdmin ? currentCompanyId : undefined,
            },
            { preserveState: true },
        );
    };

    const printHref = route('reports.balance-sheet', {
        as_of: asOf,
        print: 1,
        company_id: isAdmin ? currentCompanyId : undefined,
    });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Balance sheet
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="reports.balance-sheet"
                            routeParams={{}}
                            query={{ as_of: asOf }}
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
            <Head title="Balance sheet" />

            <div className="py-8 print:py-4">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <form
                        onSubmit={apply}
                        className="mb-6 flex flex-wrap items-end gap-3 print:hidden"
                    >
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
                        <button
                            type="submit"
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-700"
                        >
                            Apply
                        </button>
                    </form>

                    <div className="mb-4 hidden print:block">
                        <h1 className="text-2xl font-bold">Balance sheet</h1>
                        <p className="text-sm text-gray-700">As of {as_of}</p>
                    </div>

                    <div className="rounded bg-white p-6 shadow print:shadow-none">
                        <Section title="Assets" rows={report.assets} />
                        <Section
                            title="Liabilities"
                            rows={report.liabilities}
                        />
                        <Section title="Equity" rows={report.equity} />
                        <div className="flex justify-between border-t py-2 text-sm">
                            <span className="text-gray-700">
                                Net income (all-time to date, P&amp;L accounts)
                            </span>
                            <span className="tabular-nums font-medium">
                                {moneyFromCents(
                                    report.retained_earnings_cents,
                                )}
                            </span>
                        </div>
                        <div className="mt-4 flex justify-between border-t-2 border-gray-900 pt-4 text-sm font-semibold">
                            <span>Total assets</span>
                            <span className="tabular-nums">
                                {moneyFromCents(report.assets_total_cents)}
                            </span>
                        </div>
                        <div className="mt-2 flex justify-between text-sm font-semibold">
                            <span>Liabilities + equity + net income</span>
                            <span className="tabular-nums">
                                {moneyFromCents(
                                    report.liabilities_plus_equity_cents,
                                )}
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
