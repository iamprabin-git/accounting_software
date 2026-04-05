import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';

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

export default function Catalog({ templates, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const { errors } = usePage().props;
    const isAdmin = user.role === 'admin';

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const addOne = (templateId) => {
        router.post(route('chart-accounts.from-template'), {
            chart_account_template_id: templateId,
            ...(isAdmin && currentCompanyId
                ? { company_id: currentCompanyId }
                : {}),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Standard chart of accounts
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="chart-accounts.catalog"
                            routeParams={{}}
                            query={{}}
                        />
                    </div>
                </div>
            }
        >
            <Head title="Standard chart" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600">
                        Add pre-defined accounts to your company chart. Each
                        standard code can only be added once. For accounts not
                        listed here, use{' '}
                        <a
                            href={route(
                                'chart-accounts.create',
                                companyQuery,
                            )}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            Custom account
                        </a>{' '}
                        — those require admin approval before use in journals.
                    </p>

                    <InputError
                        message={errors.chart_account_template_id}
                        className="mb-4"
                    />

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
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
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {templates.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            All standard accounts are already in
                                            your chart.
                                        </td>
                                    </tr>
                                ) : (
                                    templates.map((t) => (
                                        <tr key={t.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-mono font-medium text-gray-900">
                                                {t.code}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                {t.name}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {typeLabel(t.type)}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        addOne(t.id)
                                                    }
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Add to chart
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <p className="mt-4 text-sm text-gray-500">
                        <a
                            href={route(
                                'chart-accounts.index',
                                companyQuery,
                            )}
                            className="text-indigo-600 hover:text-indigo-800"
                        >
                            ← Back to chart of accounts
                        </a>
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
