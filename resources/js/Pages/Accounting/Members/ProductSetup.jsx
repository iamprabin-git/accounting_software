import CompanyPicker from '@/Components/CompanyPicker';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function buildMemberQuery(isAdmin, currentCompanyId, member) {
    return {
        member_id: member.id,
        title: `${member.cid || member.reference_code || 'CID'} - ${member.name}`,
        ...(isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {}),
    };
}

export default function ProductSetup({
    member,
    loan_products,
    savings_products,
    chart_hints,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const memberBase = buildMemberQuery(isAdmin, currentCompanyId, member);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">
                            Member product setup
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Create loan, savings, and equity share accounts from approved member profile.
                        </p>
                    </div>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="members.products"
                        routeParams={{ member: member.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Member product setup" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Member identity</CardTitle>
                        </CardHeader>
                        <CardContent>
                        <p className="text-sm text-muted-foreground">
                            CID: <span className="font-mono">{member.cid || '—'}</span> | Name: {member.name} | Email: {member.email || '—'}
                        </p>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 lg:grid-cols-3">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Savings products</CardTitle>
                            </CardHeader>
                            <CardContent>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Interest method: simple annual rate, monthly preview, accrual scheduling.
                            </p>
                            <ul className="mt-3 space-y-2">
                                {savings_products.length === 0 ? (
                                    <li className="text-sm text-gray-500">No active savings products.</li>
                                ) : (
                                    savings_products.map((p) => (
                                        <li key={p.id}>
                                            <Link
                                                href={route('finance.positions.create', {
                                                    category: 'savings',
                                                    ...memberBase,
                                                    savings_product_id: p.id,
                                                    annual_interest_rate_percent: p.default_annual_interest_rate_percent,
                                                })}
                                                className="block rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 hover:bg-emerald-100"
                                            >
                                                {p.product_code} - {p.name} ({p.default_annual_interest_rate_percent}%)
                                            </Link>
                                        </li>
                                    ))
                                )}
                            </ul>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Loan products</CardTitle>
                            </CardHeader>
                            <CardContent>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Interest method: simple annual rate, disbursement/installment with journal transfer posting.
                            </p>
                            <ul className="mt-3 space-y-2">
                                {loan_products.length === 0 ? (
                                    <li className="text-sm text-gray-500">No active loan products.</li>
                                ) : (
                                    loan_products.map((p) => (
                                        <li key={p.id}>
                                            <Link
                                                href={route('finance.positions.create', {
                                                    category: 'loan',
                                                    ...memberBase,
                                                    loan_product_id: p.id,
                                                    annual_interest_rate_percent: p.default_annual_interest_rate_percent,
                                                })}
                                                className="block rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-900 hover:bg-indigo-100"
                                            >
                                                {p.product_code} - {p.name} ({p.default_annual_interest_rate_percent}%)
                                            </Link>
                                        </li>
                                    ))
                                )}
                            </ul>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Equity share account</CardTitle>
                            </CardHeader>
                            <CardContent>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Use investment category as equity share ledger for member.
                            </p>
                            <Link
                                href={route('finance.positions.create', {
                                    category: 'investment',
                                    ...memberBase,
                                    annual_interest_rate_percent: 0,
                                    title: `Equity Share - ${member.name}`,
                                })}
                                className="mt-3 block rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 hover:bg-amber-100"
                            >
                                Create equity share account
                            </Link>
                            <div className="mt-4 rounded border border-gray-100 bg-gray-50 p-2 text-xs text-gray-600">
                                <p className="font-medium text-gray-700">Transfer account rules</p>
                                <p className="mt-1">
                                    Select approved chart accounts for debit/credit during product movements and interest posting.
                                </p>
                                <p className="mt-1">
                                    Available approved accounts: {chart_hints.length}
                                </p>
                            </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

