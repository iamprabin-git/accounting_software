import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function typeLabel(type) {
    const labels = {
        asset: 'Asset',
        liability: 'Liability',
        equity: 'Equity',
        revenue: 'Revenue',
        expense: 'Expense',
    };
    return labels[type] ?? type;
}

export default function Index({
    accounts,
    companies,
    currentCompanyId,
    canApproveChartAccounts = false,
    canManageChartAccounts = false,
}) {
    const user = usePage().props.auth.user ?? {};
    const { errors } = usePage().props;

    const companyQuery =
        user.role === 'admin' && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const postWithCompany = (routeName, accountId) => {
        const opts =
            user.role === 'admin' && currentCompanyId
                ? { data: { company_id: currentCompanyId } }
                : {};
        router.post(
            route(routeName, { account: accountId }),
            {},
            opts,
        );
    };

    const destroy = (id) => {
        if (
            !confirm(
                'Delete this account? It must not be used on any journal lines.',
            )
        ) {
            return;
        }
        const opts =
            user.role === 'admin' && currentCompanyId
                ? { data: { company_id: currentCompanyId } }
                : {};
        router.delete(
            route('chart-accounts.destroy', { account: id }),
            opts,
        );
    };

    const handleApprovalAction = (id, action) => {
        if (action === 'approve') {
            postWithCompany('chart-accounts.approve', id);
            return;
        }
        if (
            action === 'reject' &&
            confirm(
                'Decline this proposed account? It will be removed if it is not used on any journal lines.',
            )
        ) {
            postWithCompany('chart-accounts.reject', id);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Chart of accounts
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="chart-accounts.index"
                            routeParams={{}}
                            query={{}}
                        />
                        <Link
                            href={route('chart-accounts.catalog', companyQuery)}
                            className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-800 shadow-sm transition hover:bg-gray-50"
                        >
                            Standard chart
                        </Link>
                        <Link
                            href={route('chart-accounts.create', companyQuery)}
                            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                        >
                            Custom account
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Chart of accounts" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <InputError message={errors.delete} className="mb-4" />
                    <InputError message={errors.approve} className="mb-4" />
                    <InputError message={errors.reject} className="mb-4" />
                    <InputError message={errors.status} className="mb-4" />

                    <div className="overflow-x-auto bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Code
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Standard
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Created by
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {accounts.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No accounts yet. Create one to use in
                                            journal entries.
                                        </td>
                                    </tr>
                                ) : (
                                    accounts.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                                {row.code}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {row.name}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {typeLabel(row.type)}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.template_code || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {row.approval_status ===
                                                'pending' ? (
                                                    <span className="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">
                                                        Pending company
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-900">
                                                        Approved
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.creator_name || '—'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                {row.approval_status ===
                                                    'pending' &&
                                                canApproveChartAccounts ? (
                                                    <select
                                                        defaultValue=""
                                                        className="rounded-md border-gray-300 py-1 text-xs"
                                                        onChange={(e) => {
                                                            const v =
                                                                e.target.value;
                                                            if (!v) return;
                                                            handleApprovalAction(
                                                                row.id,
                                                                v,
                                                            );
                                                            e.target.value = '';
                                                        }}
                                                    >
                                                        <option value="">
                                                            Approval action...
                                                        </option>
                                                        <option value="approve">
                                                            Approve
                                                        </option>
                                                        <option value="reject">
                                                            Reject
                                                        </option>
                                                    </select>
                                                ) : null}
                                                {canManageChartAccounts ? (
                                                    <>
                                                        <Link
                                                            href={route(
                                                                'chart-accounts.edit',
                                                                {
                                                                    account: row.id,
                                                                    ...companyQuery,
                                                                },
                                                            )}
                                                            className={`text-indigo-600 hover:text-indigo-900 ${
                                                                row.approval_status ===
                                                                    'pending' &&
                                                                canApproveChartAccounts
                                                                    ? ' ms-4'
                                                                    : ''
                                                            }`}
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
                                                ) : null}
                                                {!canManageChartAccounts &&
                                                !(
                                                    row.approval_status ===
                                                        'pending' &&
                                                    canApproveChartAccounts
                                                ) ? (
                                                    <span className="text-gray-400">
                                                        —
                                                    </span>
                                                ) : null}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {accounts.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {accounts.links.map((link, i) =>
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
