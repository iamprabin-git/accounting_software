import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { crmCompanyQuery } from '@/lib/crmNav';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Index({
    activities,
    filter,
    typeLabels,
    companies,
    currentCompanyId,
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user ?? {};
    const q = crmCompanyQuery(user, currentCompanyId);

    const destroy = (id) => {
        if (!confirm('Delete this activity?')) return;
        router.delete(route('crm.activities.destroy', { activity: id }), {
            data:
                user.role === 'admin' && currentCompanyId
                    ? { company_id: currentCompanyId }
                    : {},
        });
    };

    const complete = (id) => {
        router.post(
            route('crm.activities.complete', { activity: id }),
            user.role === 'admin' && currentCompanyId
                ? { company_id: currentCompanyId }
                : {},
            { preserveScroll: true },
        );
    };

    const tab = (label, val) => (
        <Link
            href={route('crm.activities.index', { ...q, filter: val })}
            className={`rounded-md px-3 py-1.5 text-sm font-medium ${
                filter === val
                    ? 'bg-gray-800 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
            }`}
        >
            {label}
        </Link>
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {t('nav.crmActivities')}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="crm.activities.index"
                            routeParams={{}}
                            query={{ filter }}
                        />
                        <Link
                            href={route('crm.activities.create', q)}
                            className="inline-flex rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            Log activity
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={t('nav.crmActivities')} />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-4 flex flex-wrap gap-2">
                        {tab('Open', 'open')}
                        {tab('Done', 'done')}
                        {tab('All', 'all')}
                    </div>
                    <div className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-900">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Activity
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Related to
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Due
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {activities.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No activities.
                                        </td>
                                    </tr>
                                ) : (
                                    activities.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-sm">
                                                <span className="font-medium text-gray-900 dark:text-white">
                                                    {row.title}
                                                </span>
                                                <p className="text-xs text-gray-500">
                                                    {row.type_label}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {row.subject_label}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {row.due_at
                                                    ? new Date(
                                                          row.due_at,
                                                      ).toLocaleString()
                                                    : '—'}
                                                {row.completed_at ? (
                                                    <span className="ml-2 text-green-600">
                                                        Done
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                {!row.completed_at ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            complete(row.id)
                                                        }
                                                        className="text-green-600 dark:text-green-400"
                                                    >
                                                        Complete
                                                    </button>
                                                ) : null}
                                                <Link
                                                    href={route(
                                                        'crm.activities.edit',
                                                        {
                                                            activity: row.id,
                                                            ...q,
                                                        },
                                                    )}
                                                    className="ml-3 text-indigo-600 dark:text-indigo-400"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(row.id)
                                                    }
                                                    className="ml-3 text-red-600"
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
