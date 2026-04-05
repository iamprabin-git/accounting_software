import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function Card({ title, description, href }) {
    return (
        <Link
            href={href}
            className="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
        >
            <h3 className="text-base font-semibold text-gray-900">{title}</h3>
            <p className="mt-2 text-sm text-gray-600">{description}</p>
        </Link>
    );
}

export default function BackOffice({ companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQ =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const fin = (cat) =>
        route('finance.positions.index', {
            category: cat,
            workspace: 'back',
            ...companyQ,
        });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">
                            Back office
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Approve journals, edit or remove records, manage
                            schedules, interest posting, and member approvals.
                        </p>
                    </div>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="workspace.back-office"
                            routeParams={{}}
                            query={{}}
                        />
                    )}
                </div>
            }
        >
            <Head title="Back office" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Card
                            title="Journals"
                            description="Review, edit drafts, approve or reject submitted entries."
                            href={route('journals.index', companyQ)}
                        />
                        <Card
                            title="Members"
                            description="Approve or reject members; manage details."
                            href={route('members.index', companyQ)}
                        />
                        <Card
                            title="Loan products"
                            description="Define product codes (e.g. 11) for 11-0001 style accounts and workflows."
                            href={route('finance.loan-products.index', companyQ)}
                        />
                        <Card
                            title="Savings products"
                            description="Define product codes (e.g. 01) for 01-0001 style savings accounts and workflows."
                            href={route('finance.savings-products.index', companyQ)}
                        />
                        <Card
                            title="Loans (manage)"
                            description="Edit products, schedules, interest, delete."
                            href={fin('loan')}
                        />
                        <Card
                            title="Savings (manage)"
                            description="Edit products, schedules, quarterly posting, delete."
                            href={fin('savings')}
                        />
                        <Card
                            title="Investments"
                            description="Manage investment positions and manual returns."
                            href={route('finance.positions.index', {
                                category: 'investment',
                                workspace: 'back',
                                ...companyQ,
                            })}
                        />
                        {user.can_manage_chart_of_accounts && (
                            <Card
                                title="Chart of accounts"
                                description="Maintain the general ledger account list."
                                href={route('chart-accounts.index', companyQ)}
                            />
                        )}
                        {user.can_view_reports && (
                            <Card
                                title="Reports"
                                description="Trial balance and financial reports."
                                href={route('reports.index', companyQ)}
                            />
                        )}
                        <Card
                            title="Inventory"
                            description="Stock, purchases, and sales."
                            href={route('inventory.index', companyQ)}
                        />
                        <Card
                            title="Debtors & creditors"
                            description="Receivables and payables lists."
                            href={route('debtors.index', companyQ)}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
