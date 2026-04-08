import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const bucketLabels = {
    current: 'Paid within 30 days',
    days_31_60: '31–60 days since installment',
    days_61_90: '61–90 days since installment',
    days_over_90: 'Over 90 days since installment',
    never_paid: 'No installment on file',
};

export default function ParAging({
    rows,
    summary,
    as_of,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin ? { company_id: currentCompanyId } : {};
    const parPct = (summary.par_ratio_bps / 100).toFixed(2);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            PAR / loan aging
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            As of {as_of}. Buckets use days since last{' '}
                            <strong>installment</strong> movement; loans with no
                            installment use days since start / open date.
                        </p>
                    </div>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="reports.par-aging"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="PAR / aging" />

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

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 print:hidden">
                        <Card>
                            <CardContent className="p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">
                                Outstanding principal
                            </p>
                            <p className="mt-1 text-lg font-semibold font-mono">
                                {money(summary.total_outstanding_cents)}
                            </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">
                                At-risk principal (rough PAR)
                            </p>
                            <p className="mt-1 text-lg font-semibold font-mono">
                                {money(summary.at_risk_cents)}
                            </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">
                                PAR ratio
                            </p>
                            <p className="mt-1 text-lg font-semibold">{parPct}%</p>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Bucket</th>
                                    <th className="px-3 py-2 text-right">Principal</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {Object.entries(summary.bucket_totals_cents).map(
                                    ([key, cents]) => (
                                        <tr key={key}>
                                            <td className="px-3 py-2">
                                                {bucketLabels[key] ?? key}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(cents)}
                                            </td>
                                        </tr>
                                    ),
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Loan</th>
                                    <th className="px-3 py-2 text-left">Member</th>
                                    <th className="px-3 py-2 text-left">Product</th>
                                    <th className="px-3 py-2 text-right">Outstanding</th>
                                    <th className="px-3 py-2 text-left">Bucket</th>
                                    <th className="px-3 py-2 text-right">Days</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-8 text-center text-gray-500"
                                        >
                                            No loans with outstanding principal.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-3 py-2">
                                                <span className="font-mono text-xs">
                                                    {r.account_number || '—'}
                                                </span>
                                                <br />
                                                <span className="text-gray-700">
                                                    {r.title}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2">
                                                {r.member_number != null
                                                    ? `#${r.member_number} ${r.member_name || ''}`
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2 font-mono text-xs">
                                                {r.product_code || '—'}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono">
                                                {money(r.principal_cents)}
                                            </td>
                                            <td className="px-3 py-2 text-xs">
                                                {bucketLabels[r.bucket] ?? r.bucket}
                                            </td>
                                            <td className="px-3 py-2 text-right font-mono text-xs">
                                                {r.days_since_installment != null
                                                    ? r.days_since_installment
                                                    : r.days_since_start_or_open ?? '—'}
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
