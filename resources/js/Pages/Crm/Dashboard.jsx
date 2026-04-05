import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { crmCompanyQuery } from '@/lib/crmNav';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Dashboard({
    stats,
    pipelineByStage,
    upcomingActivities,
    recentWon,
    companies,
    currentCompanyId,
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user ?? {};
    const q = crmCompanyQuery(user, currentCompanyId);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                        {t('nav.crm')} — {t('nav.crmOverview')}
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.dashboard"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`${t('nav.crm')} · ${t('nav.crmOverview')}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Pipeline and relationships for your organization. Open
                        deals exclude Won/Lost stages.
                    </p>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            ['Accounts', stats.accounts],
                            ['Contacts', stats.contacts],
                            ['Open opportunities', stats.open_opportunities],
                            [
                                'Pipeline value',
                                moneyFromCents(stats.pipeline_value_cents),
                            ],
                        ].map(([label, val]) => (
                            <div
                                key={label}
                                className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                            >
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {label}
                                </p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">
                                    {val}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-8 lg:grid-cols-2">
                        <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div className="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h3 className="font-semibold text-gray-900 dark:text-white">
                                    Pipeline by stage
                                </h3>
                            </div>
                            <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                                {pipelineByStage.length === 0 ? (
                                    <li className="px-4 py-6 text-sm text-gray-500">
                                        No open opportunities yet.
                                    </li>
                                ) : (
                                    pipelineByStage.map((row) => (
                                        <li
                                            key={row.stage}
                                            className="flex items-center justify-between px-4 py-3 text-sm"
                                        >
                                            <span className="font-medium text-gray-800 dark:text-gray-200">
                                                {row.label}
                                            </span>
                                            <span className="tabular-nums text-gray-600 dark:text-gray-400">
                                                {row.count} ·{' '}
                                                {moneyFromCents(
                                                    row.total_cents,
                                                )}
                                            </span>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </div>

                        <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div className="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h3 className="font-semibold text-gray-900 dark:text-white">
                                    Due in the next 14 days
                                </h3>
                            </div>
                            <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                                {upcomingActivities.length === 0 ? (
                                    <li className="px-4 py-6 text-sm text-gray-500">
                                        No upcoming activities.
                                    </li>
                                ) : (
                                    upcomingActivities.map((a) => (
                                        <li
                                            key={a.id}
                                            className="px-4 py-3 text-sm"
                                        >
                                            <Link
                                                href={route(
                                                    'crm.activities.edit',
                                                    {
                                                        activity: a.id,
                                                        ...q,
                                                    },
                                                )}
                                                className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                            >
                                                {a.title}
                                            </Link>
                                            <p className="text-xs text-gray-500">
                                                {a.type_label} ·{' '}
                                                {a.subject_label}
                                                {a.due_at
                                                    ? ` · ${new Date(a.due_at).toLocaleString()}`
                                                    : ''}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </div>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h3 className="font-semibold text-gray-900 dark:text-white">
                                Recently won
                            </h3>
                        </div>
                        <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                            {recentWon.length === 0 ? (
                                <li className="px-4 py-6 text-sm text-gray-500">
                                    No closed-won deals yet.
                                </li>
                            ) : (
                                recentWon.map((o) => (
                                    <li
                                        key={o.id}
                                        className="flex items-center justify-between px-4 py-3 text-sm"
                                    >
                                        <Link
                                            href={route(
                                                'crm.opportunities.edit',
                                                {
                                                    opportunity: o.id,
                                                    ...q,
                                                },
                                            )}
                                            className="font-medium text-indigo-600 dark:text-indigo-400"
                                        >
                                            {o.name}
                                        </Link>
                                        <span className="tabular-nums text-gray-600">
                                            {o.amount_cents != null
                                                ? moneyFromCents(
                                                      o.amount_cents,
                                                  )
                                                : '—'}
                                        </span>
                                    </li>
                                ))
                            )}
                        </ul>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={route('crm.accounts.create', q)}
                            className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            New account
                        </Link>
                        <Link
                            href={route('crm.contacts.create', q)}
                            className="rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            New contact
                        </Link>
                        <Link
                            href={route('crm.opportunities.create', q)}
                            className="rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            New opportunity
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
