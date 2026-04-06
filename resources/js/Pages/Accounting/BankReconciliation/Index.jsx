import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({ batches, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Bank reconciliation
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        {isAdmin ? (
                            <CompanyPicker
                                companies={companies}
                                currentCompanyId={currentCompanyId}
                                routeName="bank-reconciliation.index"
                                routeParams={{}}
                                query={{}}
                            />
                        ) : null}
                        <Link
                            href={route('bank-reconciliation.create', q)}
                            className="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
                        >
                            New import
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Bank reconciliation" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
                    <p className="text-sm text-gray-600">
                        Import bank statement lines (CSV), then match them to
                        approved journal lines on the same bank or cash account.
                        Matches are recorded in the audit trail.
                    </p>

                    {!batches?.length ? (
                        <p className="rounded border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                            No imports yet. Start with{' '}
                            <Link
                                href={route('bank-reconciliation.create', q)}
                                className="font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                New import
                            </Link>
                            .
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-200 overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                            {batches.map((b) => (
                                <li key={b.id}>
                                    <Link
                                        href={route('bank-reconciliation.show', {
                                            batch: b.id,
                                            ...q,
                                        })}
                                        className="block px-4 py-3 hover:bg-gray-50"
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <span className="font-medium text-gray-900">
                                                {b.name ||
                                                    `Import #${b.id}`}{' '}
                                                <span className="font-normal text-gray-500">
                                                    ·{' '}
                                                    {b.chart_account
                                                        ? `${b.chart_account.code} ${b.chart_account.name}`
                                                        : '—'}
                                                </span>
                                            </span>
                                            <span className="text-xs text-gray-500">
                                                {b.matched_count}/{b.lines_count}{' '}
                                                matched
                                            </span>
                                        </div>
                                        <p className="mt-1 text-xs text-gray-500">
                                            {b.created_at
                                                ? new Date(
                                                      b.created_at,
                                                  ).toLocaleString()
                                                : '—'}
                                            {b.user_name
                                                ? ` · ${b.user_name}`
                                                : ''}
                                        </p>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
