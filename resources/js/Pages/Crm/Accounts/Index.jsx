import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { crmCompanyQuery } from '@/lib/crmNav';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Index({ accounts, companies, currentCompanyId }) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user ?? {};
    const q = crmCompanyQuery(user, currentCompanyId);

    const destroy = (id) => {
        if (!confirm('Delete this account? Related contacts may be unlinked.'))
            return;
        router.delete(route('crm.accounts.destroy', { account: id }), {
            data:
                user.role === 'admin' && currentCompanyId
                    ? { company_id: currentCompanyId }
                    : {},
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {t('nav.crmAccounts')}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="crm.accounts.index"
                            routeParams={{}}
                            query={{}}
                        />
                        <Link
                            href={route('crm.accounts.create', q)}
                            className="inline-flex rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            Add account
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={t('nav.crmAccounts')} />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-900">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Industry
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {accounts.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No accounts yet.
                                        </td>
                                    </tr>
                                ) : (
                                    accounts.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                                <Link
                                                    href={route(
                                                        'crm.accounts.show',
                                                        {
                                                            account: row.id,
                                                            ...q,
                                                        },
                                                    )}
                                                    className="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                                >
                                                    {row.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {row.industry ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {row.email ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'crm.accounts.edit',
                                                        {
                                                            account: row.id,
                                                            ...q,
                                                        },
                                                    )}
                                                    className="text-indigo-600 dark:text-indigo-400"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(row.id)
                                                    }
                                                    className="ml-3 text-red-600 dark:text-red-400"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {accounts.links?.length > 3 ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {accounts.links.map((l, i) => (
                                <Link
                                    key={i}
                                    href={l.url || '#'}
                                    className={`rounded px-3 py-1 text-sm ${
                                        l.active
                                            ? 'bg-gray-800 text-white'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ))}
                        </div>
                    ) : null}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
