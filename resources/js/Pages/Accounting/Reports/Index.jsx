import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const cards = [
    {
        title: 'Trial balance',
        desc: 'Debit and credit by account; member loans/savings shown as rolled-up totals (Σ lines).',
        href: 'reports.trial-balance',
    },
    {
        title: 'Profit & loss',
        desc: 'Revenue and expenses for a period.',
        href: 'reports.profit-loss',
    },
    {
        title: 'Balance sheet',
        desc: 'Assets, liabilities, and equity; member loans/savings as single summary lines.',
        href: 'reports.balance-sheet',
    },
    {
        title: 'Cash flow (simplified)',
        desc: 'Net income plus cash / bank account movement.',
        href: 'reports.cash-flow',
    },
    {
        title: 'General ledger',
        desc: 'Posted lines and running balance for one account.',
        href: 'reports.general-ledger',
    },
    {
        title: 'Bank reconciliation',
        desc: 'Import statement CSV and match lines to approved journals.',
        href: 'bank-reconciliation.index',
    },
    {
        title: 'PAR / loan aging',
        desc: 'Outstanding loans by days since last installment (portfolio-style).',
        href: 'reports.par-aging',
    },
    {
        title: 'Statement of loans',
        desc: 'Per-loan balances with member reference, email, phone, and product.',
        href: 'reports.loan-accounts',
    },
    {
        title: 'Statement of savings',
        desc: 'Per-savings balances with member reference, email, phone, and product.',
        href: 'reports.savings-accounts',
    },
];

export default function Index({ companies, currentCompanyId, letterhead }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Financial reports
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="reports.index"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Reports" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <p className="mb-6 text-sm text-gray-600 print:hidden">
                        Figures include only <strong>approved</strong> journal
                        entries. After your company owner approves staff-prepared
                        entries, trial balance, P&amp;L, balance sheet, and
                        cash flow update automatically from the same ledger
                        data.
                    </p>

                    <ul className="grid gap-4 sm:grid-cols-2">
                        {cards.map((c) => (
                            <li key={c.href}>
                                <Link
                                    href={route(c.href, q)}
                                    className="block h-full transition"
                                >
                                    <Card className="h-full border-border/70 transition hover:border-indigo-300 hover:shadow-md">
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-base">
                                                {c.title}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-sm text-muted-foreground">
                                                {c.desc}
                                            </p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
