import CompanyPicker from '@/Components/CompanyPicker';
import PrintLetterhead from '@/Components/PrintLetterhead';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePrintWhenReady } from '@/hooks/usePrintWhenReady';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { moneyFromCents } from '@/utils/money';
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

export default function Show({
    journal,
    can_update,
    can_submit,
    can_approve,
    can_reject,
    can_delete,
    printMode,
    companies,
    currentCompanyId,
    letterhead,
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const errors = page.props.errors ?? {};
    const isAdmin = user.role === 'admin';
    const companyPost = isAdmin ? { company_id: currentCompanyId } : {};

    usePrintWhenReady(Boolean(printMode));

    const postAction = (name) => {
        router.post(route(name, { journal: journal.id }), companyPost);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            Journal #{journal.id}
                        </h2>
                        <p className="text-sm text-gray-500">
                            {journal.transaction_date}{' '}
                            <span
                                className={`ms-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusStyle(journal.status)}`}
                            >
                                {journal.status}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="journals.show"
                            routeParams={{ journal: journal.id }}
                            query={{}}
                        />
                        <Link
                            href={route('journals.show', {
                                journal: journal.id,
                                print: 1,
                                company_id: isAdmin
                                    ? currentCompanyId
                                    : undefined,
                            })}
                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Print
                        </Link>
                        {can_update && (
                            <Link
                                href={route('journals.edit', {
                                    journal: journal.id,
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                                className="rounded-md bg-gray-800 px-3 py-2 text-sm text-white hover:bg-gray-700"
                            >
                                Edit
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Journal ${journal.id}`} />

            <div className="py-8 print:py-4">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <PrintLetterhead letterhead={letterhead} />
                    <div className="mb-6 hidden print:block">
                        <h1 className="text-2xl font-bold text-black">
                            Journal entry #{journal.id}
                        </h1>
                        <p className="text-sm text-gray-700">
                            Date: {journal.transaction_date} · Status:{' '}
                            {journal.status}
                        </p>
                    </div>

                    <div className="mb-4 space-y-1 rounded bg-white p-4 shadow sm:rounded-lg print:shadow-none">
                        <p className="text-sm">
                            <span className="font-medium text-gray-600">
                                Reference:
                            </span>{' '}
                            {journal.reference || '—'}
                        </p>
                        <p className="text-sm">
                            <span className="font-medium text-gray-600">
                                Memo:
                            </span>{' '}
                            {journal.memo || '—'}
                        </p>
                        <p className="text-sm">
                            <span className="font-medium text-gray-600">
                                Created by:
                            </span>{' '}
                            {journal.creator_name || '—'}
                        </p>
                        {journal.status === 'approved' && (
                            <>
                                <p className="text-sm">
                                    <span className="font-medium text-gray-600">
                                        Approved by:
                                    </span>{' '}
                                    {journal.approved_by_name || '—'}
                                </p>
                                <p className="text-sm">
                                    <span className="font-medium text-gray-600">
                                        Approved at:
                                    </span>{' '}
                                    {journal.approved_at
                                        ? formatDisplayDateTime(
                                              journal.approved_at,
                                          )
                                        : '—'}
                                </p>
                            </>
                        )}
                    </div>

                    <div className="overflow-hidden rounded bg-white shadow print:shadow-none">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 print:bg-white">
                                <tr>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">
                                        Account
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium text-gray-600">
                                        Debit
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium text-gray-600">
                                        Credit
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">
                                        Note
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {journal.lines.map((line) => (
                                    <tr key={line.id}>
                                        <td className="px-4 py-2 text-gray-900">
                                            {line.account_code} —{' '}
                                            {line.account_name}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {line.debit_cents > 0
                                                ? moneyFromCents(line.debit_cents)
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {line.credit_cents > 0
                                                ? moneyFromCents(
                                                      line.credit_cents,
                                                  )
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-2 text-gray-600">
                                            {line.description || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-6 flex flex-wrap gap-2 print:hidden">
                        {can_submit && (
                            <button
                                type="button"
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                                onClick={() => postAction('journals.submit')}
                            >
                                Submit for approval
                            </button>
                        )}
                        {can_approve && (
                            <button
                                type="button"
                                className="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-600"
                                onClick={() => postAction('journals.approve')}
                            >
                                Approve
                            </button>
                        )}
                        {can_reject && (
                            <button
                                type="button"
                                className="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500"
                                onClick={() => postAction('journals.reject')}
                            >
                                Return to draft
                            </button>
                        )}
                        {can_delete && (
                            <button
                                type="button"
                                className="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                                onClick={() => {
                                    if (
                                        confirm(
                                            'Delete this journal entry permanently?',
                                        )
                                    ) {
                                        router.delete(
                                            route('journals.destroy', {
                                                journal: journal.id,
                                            }),
                                            { data: companyPost },
                                        );
                                    }
                                }}
                            >
                                Delete
                            </button>
                        )}
                        <Link
                            href={route('journals.index', {
                                company_id: isAdmin
                                    ? currentCompanyId
                                    : undefined,
                            })}
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Back to list
                        </Link>
                    </div>

                    {errors?.approve && (
                        <p className="mt-4 text-sm text-red-600 print:hidden">
                            {errors.approve}
                        </p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
