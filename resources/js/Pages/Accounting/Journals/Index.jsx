import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function statusStyle(status) {
    switch (status) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-amber-100 text-amber-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

export default function Index({
    journalEntries,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const user = usePage().props.auth.user ?? {};

    const companyQuery =
        user.role === 'admin' && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Journal entries
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="journals.index"
                            routeParams={{}}
                            query={{}}
                        />
                        {user.can_create_journals && (
                            <>
                                <Link
                                    href={route('journals.create', companyQuery)}
                                    className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                                >
                                    New journal
                                </Link>
                                <Link
                                    href={route(
                                        'journals.create-cash-in',
                                        companyQuery,
                                    )}
                                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-800 shadow-sm transition hover:bg-gray-50"
                                >
                                    Cash in
                                </Link>
                                <Link
                                    href={route(
                                        'journals.create-cash-out',
                                        companyQuery,
                                    )}
                                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-800 shadow-sm transition hover:bg-gray-50"
                                >
                                    Cash out
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Journals" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <p className="mb-4 text-sm text-gray-600 print:hidden">
                        Staff draft entries and submit them for approval. Company
                        owners approve; only approved entries appear in financial
                        reports.
                    </p>

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Date
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Reference
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Posted No.
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Pending age
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Lines
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        By
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {journalEntries.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No journal entries yet.
                                        </td>
                                    </tr>
                                ) : (
                                    journalEntries.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                                {row.transaction_date}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {row.reference || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {row.posted_number ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusStyle(row.status)}`}
                                                >
                                                    {row.status}
                                                </span>
                                                {row.status === 'pending' &&
                                                row.first_approved_by_name ? (
                                                    <div className="mt-1 text-xs text-gray-600">
                                                        First approved by{' '}
                                                        {row.first_approved_by_name}
                                                    </div>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.status === 'pending' &&
                                                row.pending_age_days != null
                                                    ? `${row.pending_age_days} day(s)`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.lines_count}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.creator_name || '—'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'journals.show',
                                                        {
                                                            journal: row.id,
                                                            ...companyQuery,
                                                        },
                                                    )}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {journalEntries.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1 print:hidden">
                            {journalEntries.links.map((link, i) =>
                                link.url ? (
                                    <button
                                        key={i}
                                        type="button"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-gray-800 text-white'
                                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                        }`}
                                        onClick={() =>
                                            router.get(link.url, {}, {
                                                preserveState: true,
                                            })
                                        }
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className="px-3 py-1 text-sm text-gray-400"
                                    />
                                ),
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
