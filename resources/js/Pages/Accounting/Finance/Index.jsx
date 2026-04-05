import LoanSavingsProductModal from '@/Components/LoanSavingsProductModal';
import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const TABS = [
    { category: 'loan', label: 'Loans' },
    { category: 'investment', label: 'Investments' },
    { category: 'savings', label: 'Savings' },
];

export default function Index({
    category,
    categoryLabel,
    workspace = 'full',
    positions,
    totals,
    modal_chart_accounts = [],
    companies,
    currentCompanyId,
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};
    const listQuery = useMemo(
        () => ({
            ...companyQuery,
            ...(workspace === 'front' || workspace === 'back'
                ? { workspace }
                : {}),
        }),
        [companyQuery, workspace],
    );

    const showInitiate = workspace !== 'back';
    const showManage = workspace !== 'front';
    const showSchedule = workspace !== 'front';
    const showProductColumn =
        category === 'loan' || category === 'savings';

    const [modalRow, setModalRow] = useState(null);

    const tabHref = (cat) =>
        route('finance.positions.index', {
            category: cat,
            ...listQuery,
        });

    const destroy = (id) => {
        if (!confirm('Remove this record?')) return;
        const opts =
            isAdmin && currentCompanyId
                ? { data: { company_id: currentCompanyId } }
                : {};
        router.delete(
            route('finance.positions.destroy', {
                category,
                position: id,
            }),
            opts,
        );
    };

    const colCount = showProductColumn ? 10 : 8;

    const workspaceTitle =
        workspace === 'front'
            ? 'Front desk — '
            : workspace === 'back'
              ? 'Back office — '
              : '';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {workspaceTitle}
                        Finance — {categoryLabel}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="finance.positions.index"
                            routeParams={{ category }}
                            query={
                                workspace === 'front' || workspace === 'back'
                                    ? { workspace }
                                    : {}
                            }
                        />
                        {showInitiate && (
                            <Link
                                href={route('finance.positions.create', {
                                    category,
                                    ...listQuery,
                                })}
                                className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                            >
                                Add record
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={categoryLabel} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {workspace === 'front' && (
                        <p className="mb-4 rounded-lg border border-indigo-100 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-950">
                            <strong>Front desk:</strong> initiate products and
                            use <strong>Product actions</strong> for deposits,
                            withdrawals, and statements. Schedules and approvals
                            are in the back office.
                        </p>
                    )}
                    {workspace === 'back' && (
                        <p className="mb-4 rounded-lg border border-amber-100 bg-amber-50/80 px-4 py-3 text-sm text-amber-950">
                            <strong>Back office:</strong> edit products, run
                            interest schedules, post to the ledger, and delete
                            records. Use the workspace menu for journals and
                            member approvals.
                        </p>
                    )}

                    <nav className="mb-6 flex flex-wrap gap-2 print:hidden">
                        {TABS.map((t) => (
                            <Link
                                key={t.category}
                                href={tabHref(t.category)}
                                className={`rounded-md px-3 py-2 text-sm font-medium ${
                                    t.category === category
                                        ? 'bg-gray-800 text-white'
                                        : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                }`}
                            >
                                {t.label}
                            </Link>
                        ))}
                    </nav>

                    <p className="mb-4 text-sm text-gray-600">
                        {category === 'investment' ? (
                            <>
                                Enter principal or carrying value.{' '}
                                <strong>Returns are manual</strong> on each
                                position&apos;s schedule page (not from an annual
                                %).
                            </>
                        ) : (
                            <>
                                Enter principal or balance and an{' '}
                                <strong>annual interest rate %</strong>. Interest
                                is calculated as <strong>simple interest</strong>{' '}
                                (365-day year): yearly = P×R/100, monthly ≈
                                yearly÷12. Use the <strong>Schedule</strong> link
                                to accrue monthly and post loan interest when
                                paid, or to post savings interest quarterly.
                            </>
                        )}
                    </p>

                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                        <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                            <span className="text-gray-600">Total principal: </span>
                            <span className="font-semibold tabular-nums text-gray-900">
                                {moneyFromCents(totals.principal_cents)}
                            </span>
                        </div>
                        <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                            <span className="text-gray-600">
                                Total annual interest (sum of rows):{' '}
                            </span>
                            <span className="font-semibold tabular-nums text-gray-900">
                                {moneyFromCents(totals.annual_interest_cents)}
                            </span>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Title
                                    </th>
                                    {showProductColumn && (
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Account
                                        </th>
                                    )}
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Member
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Principal
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Rate % p.a.
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Interest / yr
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Interest / mo
                                    </th>
                                    {showProductColumn && (
                                        <th className="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                            Product
                                        </th>
                                    )}
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Schedule
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {positions.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={colCount}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No records in this category.
                                        </td>
                                    </tr>
                                ) : (
                                    positions.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                                {row.title}
                                            </td>
                                            {showProductColumn && (
                                                <td className="px-4 py-3 font-mono text-sm tabular-nums text-gray-800">
                                                    {row.uses_structured_loan &&
                                                    row.loan_workflow_status ===
                                                        'pending_approval' &&
                                                    row.proposed_account_number ? (
                                                        <span className="text-amber-900">
                                                            {
                                                                row.proposed_account_number
                                                            }
                                                            <span className="block text-xs font-normal text-amber-800">
                                                                pending
                                                            </span>
                                                        </span>
                                                    ) : row.uses_structured_loan &&
                                                      row.loan_workflow_status ===
                                                          'rejected' ? (
                                                        <span className="text-sm text-red-700">
                                                            Rejected
                                                        </span>
                                                    ) : row.uses_structured_savings &&
                                                      row.savings_workflow_status ===
                                                          'pending_approval' &&
                                                      row.proposed_account_number ? (
                                                        <span className="text-amber-900">
                                                            {
                                                                row.proposed_account_number
                                                            }
                                                            <span className="block text-xs font-normal text-amber-800">
                                                                pending
                                                            </span>
                                                        </span>
                                                    ) : row.uses_structured_savings &&
                                                      row.savings_workflow_status ===
                                                          'rejected' ? (
                                                        <span className="text-sm text-red-700">
                                                            Rejected
                                                        </span>
                                                    ) : row.account_number ? (
                                                        row.account_number
                                                    ) : (
                                                        <span className="text-gray-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            )}
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {row.member_name ? (
                                                    <>
                                                        {row.member_number !=
                                                            null && (
                                                            <span className="font-medium tabular-nums text-gray-900">
                                                                #
                                                                {
                                                                    row.member_number
                                                                }{' '}
                                                            </span>
                                                        )}
                                                        {row.member_name}
                                                        {row.member_status &&
                                                            row.member_status !==
                                                                'approved' && (
                                                                <span className="ms-1 text-xs text-amber-700">
                                                                    (
                                                                    {
                                                                        row.member_status
                                                                    }
                                                                    )
                                                                </span>
                                                            )}
                                                    </>
                                                ) : (
                                                    <span className="text-gray-400">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-800">
                                                {moneyFromCents(
                                                    row.principal_cents,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-700">
                                                {Number(
                                                    row.annual_interest_rate_percent,
                                                ).toLocaleString(undefined, {
                                                    maximumFractionDigits: 4,
                                                })}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-800">
                                                {moneyFromCents(
                                                    row.annual_interest_cents,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-800">
                                                {moneyFromCents(
                                                    row.monthly_interest_cents,
                                                )}
                                            </td>
                                            {showProductColumn && (
                                                <td className="px-4 py-3 text-center text-sm">
                                                    {(row.uses_structured_loan &&
                                                        !row.loan_operational) ||
                                                    (row.uses_structured_savings &&
                                                        !row.savings_operational) ? (
                                                        <span className="text-xs text-amber-800">
                                                            Awaiting approval
                                                        </span>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setModalRow(row)
                                                            }
                                                            className="rounded-md bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                                                        >
                                                            Actions
                                                        </button>
                                                    )}
                                                </td>
                                            )}
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                {showSchedule ? (
                                                    <Link
                                                        href={route(
                                                            'finance.positions.show',
                                                            {
                                                                category,
                                                                position:
                                                                    row.id,
                                                                ...listQuery,
                                                                ...(workspace ===
                                                                    'front' ||
                                                                workspace ===
                                                                    'back'
                                                                    ? {
                                                                          workspace,
                                                                      }
                                                                    : {}),
                                                            },
                                                        )}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        Open
                                                    </Link>
                                                ) : (
                                                    <span className="text-gray-400">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                {showManage ? (
                                                    <>
                                                        <Link
                                                            href={route(
                                                                'finance.positions.edit',
                                                                {
                                                                    category,
                                                                    position:
                                                                        row.id,
                                                                    ...listQuery,
                                                                },
                                                            )}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                destroy(row.id)
                                                            }
                                                            className="ms-4 text-red-600 hover:text-red-800"
                                                        >
                                                            Delete
                                                        </button>
                                                    </>
                                                ) : (
                                                    <span className="text-xs text-gray-400">
                                                        Back office
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {positions.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {positions.links.map((link, i) =>
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

            <LoanSavingsProductModal
                open={modalRow !== null}
                onClose={() => setModalRow(null)}
                category={category}
                row={modalRow}
                companyQuery={companyQuery}
                isAdmin={isAdmin}
                currentCompanyId={currentCompanyId}
                chartAccounts={modal_chart_accounts}
            />
        </AuthenticatedLayout>
    );
}
