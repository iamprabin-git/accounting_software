import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

function UserTable({
    title,
    description,
    members,
    emptyMessage,
    memberKind = 'end_user',
}) {
    return (
        <div className="overflow-hidden bg-white shadow sm:rounded-lg">
            <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 className="text-base font-semibold text-gray-900">{title}</h3>
                {description ? (
                    <p className="mt-1 text-sm text-gray-600">{description}</p>
                ) : null}
            </div>
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Name
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Email
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Active
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Portal
                        </th>
                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                    {members.length === 0 ? (
                        <tr>
                            <td
                                colSpan={5}
                                className="px-6 py-8 text-center text-sm text-gray-500"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        members.map((m) => (
                            <tr key={m.id}>
                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {m.name}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {m.email}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {memberKind === 'staff' && !m.is_active ? (
                                        <div className="flex flex-col gap-2">
                                            <span>Pending activation</span>
                                            <button
                                                type="button"
                                                className="text-left text-indigo-600 hover:text-indigo-900"
                                                onClick={() =>
                                                    router.post(
                                                        route(
                                                            'company.team.activate',
                                                            m.id,
                                                        ),
                                                    )
                                                }
                                            >
                                                Activate account
                                            </button>
                                        </div>
                                    ) : m.is_active ? (
                                        'Yes'
                                    ) : (
                                        'No'
                                    )}
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-600">
                                    {m.role === 'end_user' ? (
                                        <div className="flex flex-col gap-2">
                                            <span>
                                                {m.portal_approved_at
                                                    ? 'Approved'
                                                    : 'Pending approval'}
                                            </span>
                                            <div className="flex flex-wrap gap-2">
                                                {!m.portal_approved_at && (
                                                    <button
                                                        type="button"
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'company.team.approve-portal',
                                                                    m.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Approve portal
                                                    </button>
                                                )}
                                                {m.portal_approved_at && (
                                                    <button
                                                        type="button"
                                                        className="text-amber-700 hover:text-amber-900"
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    `Revoke portal access for ${m.name}?`,
                                                                )
                                                            ) {
                                                                router.post(
                                                                    route(
                                                                        'company.team.revoke-portal',
                                                                        m.id,
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        Revoke
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ) : (
                                        <span className="text-gray-400">—</span>
                                    )}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link
                                        href={route(
                                            'company.team.edit',
                                            m.id,
                                        )}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        className="ms-4 text-red-600 hover:text-red-800"
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Remove ${m.name} from your organization?`,
                                                )
                                            ) {
                                                router.delete(
                                                    route(
                                                        'company.team.destroy',
                                                        m.id,
                                                    ),
                                                );
                                            }
                                        }}
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}

export default function Index({ staffMembers, endUserMembers }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Users
                    </h2>
                    <Link
                        href={route('company.team.create')}
                        className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900"
                    >
                        Add user
                    </Link>
                </div>
            }
        >
            <Head title="Users" />

            <div className="space-y-10 py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <p className="mb-6 text-sm text-gray-600">
                        Manage staff and customer portal logins for your
                        organization. Edit accounts, activate or deactivate
                        access, and approve end users for the member portal.
                    </p>
                    <UserTable
                        title="Staff"
                        description="New staff accounts stay inactive until you activate them. They can then sign in and draft journals and other work for your approval."
                        members={staffMembers}
                        emptyMessage="No staff users yet. Add a user and choose the Staff role."
                        memberKind="staff"
                    />
                </div>
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <UserTable
                        title="End users"
                        description="End users sign in to the customer app. Approve portal access after their member record is set up."
                        members={endUserMembers}
                        emptyMessage="No end users yet. Add a user and choose the End user role."
                        memberKind="end_user"
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
