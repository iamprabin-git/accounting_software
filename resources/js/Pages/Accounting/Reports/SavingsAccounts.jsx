import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export default function SavingsAccounts({
    rows,
    summary,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            Savings accounts list
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Total savings and individual member savings accounts.
                        </p>
                    </div>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="reports.savings-accounts"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Savings accounts report" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <p className="text-sm print:hidden">
                        <Link
                            href={route('reports.index', q)}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            ← Reports
                        </Link>
                    </p>
                    <PrintLetterhead letterhead={letterhead} />

                    <div className="grid gap-4 sm:grid-cols-2 print:hidden">
                        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p className="text-xs font-medium uppercase text-gray-500">
                                Total savings accounts
                            </p>
                            <p className="mt-1 text-lg font-semibold">
                                {summary.accounts_total}
                            </p>
                        </div>
                        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p className="text-xs font-medium uppercase text-gray-500">
                                Total savings balance
                            </p>
                            <p className="mt-1 text-lg font-semibold font-mono">
                                {money(summary.principal_total_cents)}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Account #</th>
                                    <th className="px-3 py-2 text-left">Member</th>
                                    <th className="px-3 py-2 text-left">Product</th>
                                    <th className="px-3 py-2 text-left">Status</th>
                                    <th className="px-3 py-2 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-8 text-center text-gray-500"
                                        >
                                            No savings accounts found.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-3 py-2 font-mono text-xs">
                                                {r.account_number || '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {r.member_number != null
                                                    ? `#${r.member_number} ${r.member_name || ''}`
                                                    : (r.member_name || '—')}
                                            </td>
                                            <td className="px-3 py-2">
                                                <span className="font-mono text-xs">
                                                    {r.product_code || '—'}
                                                </span>{' '}
                                                {r.product_name || ''}
                                            </td>
                                            <td className="px-3 py-2 text-xs">
                                                {r.workflow_status || 'active'}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(r.principal_cents)}
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

