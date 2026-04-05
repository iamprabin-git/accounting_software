import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { crmCompanyQuery } from '@/lib/crmNav';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Show({
    account,
    companies,
    currentCompanyId,
    stageLabels,
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user ?? {};
    const q = crmCompanyQuery(user, currentCompanyId);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {account.name}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="crm.accounts.show"
                            routeParams={{ account: account.id }}
                            query={{}}
                        />
                        <Link
                            href={route('crm.accounts.edit', {
                                account: account.id,
                                ...q,
                            })}
                            className="text-sm font-medium text-indigo-600 dark:text-indigo-400"
                        >
                            Edit
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={account.name} />
            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <dl className="grid gap-4 sm:grid-cols-2">
                        {[
                            ['Industry', account.industry],
                            ['Website', account.website],
                            ['Phone', account.phone],
                            ['Email', account.email],
                        ].map(([k, v]) => (
                            <div key={k}>
                                <dt className="text-xs font-medium uppercase text-gray-500">
                                    {k}
                                </dt>
                                <dd className="text-sm text-gray-900 dark:text-white">
                                    {v || '—'}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    {account.address ? (
                        <div>
                            <h3 className="text-sm font-medium text-gray-500">
                                Address
                            </h3>
                            <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">
                                {account.address}
                            </p>
                        </div>
                    ) : null}
                    {account.notes ? (
                        <div>
                            <h3 className="text-sm font-medium text-gray-500">
                                Notes
                            </h3>
                            <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">
                                {account.notes}
                            </p>
                        </div>
                    ) : null}

                    <div>
                        <div className="mb-2 flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Contacts
                            </h3>
                            <Link
                                href={route('crm.contacts.create', q)}
                                className="text-sm text-indigo-600 dark:text-indigo-400"
                            >
                                Add contact
                            </Link>
                        </div>
                        <ul className="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            {account.contacts.length === 0 ? (
                                <li className="px-4 py-6 text-sm text-gray-500">
                                    No contacts linked.
                                </li>
                            ) : (
                                account.contacts.map((c) => (
                                    <li
                                        key={c.id}
                                        className="flex items-center justify-between px-4 py-3 text-sm"
                                    >
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {c.name}
                                        </span>
                                        <Link
                                            href={route('crm.contacts.edit', {
                                                contact: c.id,
                                                ...q,
                                            })}
                                            className="text-indigo-600 dark:text-indigo-400"
                                        >
                                            Edit
                                        </Link>
                                    </li>
                                ))
                            )}
                        </ul>
                    </div>

                    <div>
                        <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                            Recent opportunities
                        </h3>
                        <ul className="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            {account.opportunities.length === 0 ? (
                                <li className="px-4 py-6 text-sm text-gray-500">
                                    No opportunities.
                                </li>
                            ) : (
                                account.opportunities.map((o) => (
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
                                        <span className="text-gray-600 dark:text-gray-400">
                                            {stageLabels[o.stage] ?? o.stage}
                                            {o.amount_cents != null
                                                ? ` · ${moneyFromCents(o.amount_cents)}`
                                                : ''}
                                        </span>
                                    </li>
                                ))
                            )}
                        </ul>
                    </div>

                    <Link
                        href={route('crm.accounts.index', q)}
                        className="text-sm text-gray-600 underline dark:text-gray-400"
                    >
                        ← {t('nav.crmAccounts')}
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
