import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { crmCompanyQuery } from '@/lib/crmNav';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Index({
    opportunities,
    stageLabels,
    companies,
    currentCompanyId,
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user ?? {};
    const q = crmCompanyQuery(user, currentCompanyId);

    const destroy = (id) => {
        if (!confirm('Delete this opportunity?')) return;
        router.delete(route('crm.opportunities.destroy', { opportunity: id }), {
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
                        {t('nav.crmOpportunities')}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="crm.opportunities.index"
                            routeParams={{}}
                            query={{}}
                        />
                        <Link
                            href={route('crm.opportunities.create', q)}
                            className="inline-flex rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            Add opportunity
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={t('nav.crmOpportunities')} />
            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-900">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Deal
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Stage
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Amount
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Account / Contact
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {opportunities.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No opportunities yet.
                                        </td>
                                    </tr>
                                ) : (
                                    opportunities.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                                {row.name}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {stageLabels[row.stage] ??
                                                    row.stage}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm tabular-nums text-gray-800 dark:text-gray-200">
                                                {row.amount_cents != null
                                                    ? moneyFromCents(
                                                          row.amount_cents,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {row.account_name ??
                                                    '—'}
                                                {row.contact_name
                                                    ? ` · ${row.contact_name}`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'crm.opportunities.edit',
                                                        {
                                                            opportunity:
                                                                row.id,
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
