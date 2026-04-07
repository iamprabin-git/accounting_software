import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

function Card({ title, description, href, disabled = false }) {
    const cls = disabled
        ? 'pointer-events-none block rounded-xl border border-slate-200 bg-slate-100 p-5 text-slate-400'
        : 'block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md';
    const targetHref = disabled ? '#' : href;

    return (
        <Link href={targetHref} className={cls}>
            <h3 className={`text-base font-semibold ${disabled ? 'text-slate-500' : 'text-gray-900'}`}>
                {title}
            </h3>
            <p className={`mt-2 text-sm ${disabled ? 'text-slate-400' : 'text-gray-600'}`}>
                {description}
            </p>
            {disabled ? (
                <p className="mt-3 text-xs font-medium text-amber-700">
                    Start teller day first
                </p>
            ) : null}
        </Link>
    );
}

export default function FrontDesk({
    companies,
    currentCompanyId,
    tellerDayOpenToday = false,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQ =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const fin = (cat) =>
        route('finance.positions.index', {
            category: cat,
            workspace: 'front',
            ...companyQ,
        });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">
                            Front desk
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Capture journals, deposits, withdrawals, and new
                            loan or savings products. Approvals and structural
                            edits are in the back office.
                        </p>
                    </div>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="workspace.front-desk"
                            routeParams={{}}
                            query={{}}
                        />
                    )}
                </div>
            }
        >
            <Head title="Front desk" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {!tellerDayOpenToday ? (
                        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Teller day is not started for today. Cash operations are disabled until Start day is completed.
                        </div>
                    ) : null}
                    <div className="grid gap-4 sm:grid-cols-2">
                        {user.can_create_journals && (
                            <Card
                                title="New journal entry"
                                description="Create non-cash general journal entries (always enabled)."
                                href={route('journals.create', companyQ)}
                            />
                        )}
                        <Card
                            title="New loan product"
                            description="Open a loan position for an approved member."
                            href={route('finance.positions.create', {
                                category: 'loan',
                                workspace: 'front',
                                ...companyQ,
                            })}
                            disabled={!tellerDayOpenToday}
                        />
                        <Card
                            title="New savings product"
                            description="Open a savings position for an approved member."
                            href={route('finance.positions.create', {
                                category: 'savings',
                                workspace: 'front',
                                ...companyQ,
                            })}
                            disabled={!tellerDayOpenToday}
                        />
                        <Card
                            title="Loan products (list)"
                            description="View products, deposits, withdrawals, and statements."
                            href={fin('loan')}
                            disabled={!tellerDayOpenToday}
                        />
                        <Card
                            title="Savings products (list)"
                            description="View products, deposits, withdrawals, and statements."
                            href={fin('savings')}
                            disabled={!tellerDayOpenToday}
                        />
                        <Card
                            title="Account number entry"
                            description="Look up by account number for deposits, withdrawals, statements, and adjustments."
                            href={route('finance.account-entry', companyQ)}
                            disabled={!tellerDayOpenToday}
                        />
                        {user.can_create_journals && (
                            <>
                                <Card
                                    title="Cash receive"
                                    description="Create cash-in journal (available only after Start day)."
                                    href={route('journals.create-cash-in', companyQ)}
                                    disabled={!tellerDayOpenToday}
                                />
                                <Card
                                    title="Cash payment"
                                    description="Create cash-out journal (available only after Start day)."
                                    href={route('journals.create-cash-out', companyQ)}
                                    disabled={!tellerDayOpenToday}
                                />
                            </>
                        )}
                        <Card
                            title="Teller day close"
                            description="Record cash float, counted balance, and optional expected cash for the day."
                            href={route('teller.day-close.create', companyQ)}
                        />
                        <Card
                            title="Financial statements"
                            description="Open trial balance, profit and loss, balance sheet, and cash flow."
                            href={route('reports.index', companyQ)}
                        />
                        {user.can_register_members && (
                            <Card
                                title="Register member"
                                description="Add a pending member for company approval."
                                href={route('members.create', companyQ)}
                                disabled={!tellerDayOpenToday}
                            />
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
