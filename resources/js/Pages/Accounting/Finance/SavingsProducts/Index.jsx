import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ products, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQ =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const destroy = (id) => {
        if (!confirm('Remove this savings product?')) return;
        router.delete(
            route('finance.savings-products.destroy', { savingsProduct: id }),
            { data: companyQ },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-900">
                        Savings products
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        {companies?.length > 0 && (
                            <CompanyPicker
                                companies={companies}
                                currentCompanyId={currentCompanyId}
                                routeName="finance.savings-products.index"
                                routeParams={{}}
                                query={{}}
                            />
                        )}
                        <Link
                            href={route(
                                'finance.savings-products.create',
                                companyQ,
                            )}
                            className="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        >
                            New product
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Savings products" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600">
                        Define products (e.g. code{' '}
                        <span className="font-mono">01</span>) so member savings
                        accounts can be numbered{' '}
                        <span className="font-mono">01-0001</span>,{' '}
                        <span className="font-mono">01-0002</span>, … after back
                        office approval.
                    </p>
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
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Default rate %
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {products.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No savings products yet.
                                        </td>
                                    </tr>
                                ) : (
                                    products.map((p) => (
                                        <tr key={p.id}>
                                            <td className="px-4 py-3 font-mono text-sm font-medium tabular-nums text-gray-900">
                                                {p.product_code}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-800">
                                                {p.name}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm tabular-nums text-gray-700">
                                                {Number(
                                                    p.default_annual_interest_rate_percent,
                                                ).toLocaleString(undefined, {
                                                    maximumFractionDigits: 4,
                                                })}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {p.is_active ? (
                                                    <span className="text-green-800">
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-500">
                                                        Inactive
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'finance.savings-products.edit',
                                                        {
                                                            savingsProduct: p.id,
                                                            ...companyQ,
                                                        },
                                                    )}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(p.id)
                                                    }
                                                    className="ms-3 text-red-600 hover:text-red-800"
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
