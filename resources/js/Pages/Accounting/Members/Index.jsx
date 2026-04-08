import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function statusBadge(status) {
    const map = {
        pending:
            'bg-amber-100 text-amber-900 ring-amber-200',
        approved:
            'bg-green-100 text-green-900 ring-green-200',
        rejected: 'bg-red-100 text-red-900 ring-red-200',
    };
    return map[status] ?? 'bg-gray-100 text-gray-800 ring-gray-200';
}

export default function Index({
    members,
    companies,
    currentCompanyId,
    can_create,
    can_approve,
}) {
    const user = usePage().props.auth.user ?? {};
    const { errors } = usePage().props;
    const isAdmin = user.role === 'admin';
    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const approve = (id) => {
        const data = {};
        if (isAdmin && currentCompanyId) {
            data.company_id = currentCompanyId;
        }
        router.post(route('members.approve', { member: id }), data);
    };

    const reject = (id) => {
        const reason = window.prompt('Reason (optional):') ?? '';
        const data = { rejection_reason: reason };
        if (isAdmin && currentCompanyId) {
            data.company_id = currentCompanyId;
        }
        router.post(route('members.reject', { member: id }), data, {
            preserveScroll: true,
        });
    };

    const handleApprovalAction = (id, action) => {
        if (action === 'approve') {
            approve(id);
            return;
        }
        if (action === 'reject') {
            reject(id);
        }
    };

    const destroy = (id) => {
        if (!confirm('Remove this member?')) return;
        const opts =
            isAdmin && currentCompanyId
                ? { data: { company_id: currentCompanyId } }
                : {};
        router.delete(route('members.destroy', { member: id }), opts);
    };

    const canEditRow = (m) => {
        if (user.role === 'admin') return true;
        if (user.role === 'company') return true;
        if (user.role === 'staff' && m.status === 'pending') return true;
        return false;
    };

    const canDeleteRow = (m) => {
        if (user.role === 'admin') return true;
        if (user.role === 'company') return true;
        if (
            user.role === 'staff' &&
            (m.status === 'pending' || m.status === 'rejected')
        )
            return true;
        return false;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Members
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="members.index"
                            routeParams={{}}
                            query={{}}
                        />
                        {can_create && (
                            <Link href={route('members.create', companyQuery)}>
                                <Button size="sm">Register member</Button>
                            </Link>
                        )}
                        <Link href={route('member-groups.index', companyQuery)}>
                            <Button variant="outline" size="sm">
                                Member groups
                            </Button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Members" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <InputError message={errors.approve} className="mb-2" />
                    <InputError message={errors.reject} className="mb-2" />
                    <InputError message={errors.status} className="mb-2" />
                    <p className="mb-4 text-sm text-gray-600">
                        Staff register members; the company account approves them.
                        Each member gets a permanent <strong>member number</strong>.
                        Finance (loans, savings, investments) uses that number; posted
                        journals appear on the member&apos;s individual ledger by type.
                    </p>

                    <div className="overflow-x-auto rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        No.
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Reference
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Contact
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {members.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No members yet.
                                        </td>
                                    </tr>
                                ) : (
                                    members.data.map((m) => (
                                        <tr key={m.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-semibold tabular-nums text-gray-900">
                                                #{m.member_number ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                                {m.name}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {m.reference_code || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <span
                                                    className={`inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${statusBadge(
                                                        m.status,
                                                    )}`}
                                                >
                                                    {m.status}
                                                </span>
                                            </td>
                                            <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-600">
                                                {[m.email, m.phone]
                                                    .filter(Boolean)
                                                    .join(' · ') || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <span className="inline-flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                                    {m.status === 'approved' && (
                                                        <Link
                                                            href={route(
                                                                'members.ledger',
                                                                {
                                                                    member: m.id,
                                                                    ...companyQuery,
                                                                },
                                                            )}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Ledger
                                                        </Link>
                                                    )}
                                                    {m.status === 'approved' && (
                                                        <Link
                                                            href={route(
                                                                'members.products',
                                                                {
                                                                    member: m.id,
                                                                    ...companyQuery,
                                                                },
                                                            )}
                                                            className="text-sky-700 hover:text-sky-900"
                                                        >
                                                            Products
                                                        </Link>
                                                    )}
                                                    {can_approve &&
                                                        m.status ===
                                                            'pending' && (
                                                            <select
                                                                defaultValue=""
                                                                className="rounded-md border-gray-300 py-1 text-xs"
                                                                onChange={(
                                                                    e,
                                                                ) => {
                                                                    const v =
                                                                        e.target
                                                                            .value;
                                                                    if (!v)
                                                                        return;
                                                                    handleApprovalAction(
                                                                        m.id,
                                                                        v,
                                                                    );
                                                                    e.target.value =
                                                                        '';
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
                                                        )}
                                                    {canEditRow(m) && (
                                                        <Link
                                                            href={route(
                                                                'members.edit',
                                                                {
                                                                    member: m.id,
                                                                    ...companyQuery,
                                                                },
                                                            )}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Edit
                                                        </Link>
                                                    )}
                                                    {canDeleteRow(m) && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                destroy(m.id)
                                                            }
                                                            className="text-red-600 hover:text-red-800"
                                                        >
                                                            Delete
                                                        </button>
                                                    )}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {members.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {members.links.map((link, i) =>
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
